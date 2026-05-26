<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Chargededuct;
use App\Models\Income;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Product;
use App\Models\User;
use App\Notifications\AdminBroadcastNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Centralized service for handling order delivery outcomes.
 *
 * Replaces the inline logic previously duplicated in:
 * - Backend\OrderController (admin status change)
 * - SteadfastOrderStatusService (Carrybee auto-sync)
 * - VendorOrderController (vendor-side sync)
 *
 * Ensures reseller balance, vendor earnings, income records, and
 * notifications are always applied consistently regardless of
 * which path triggers the status change.
 */
class OrderDeliveryService
{
    /**
     * Mark an order as successfully delivered.
     * Idempotent — safe to call multiple times for the same order.
     *
     * Handles:
     * 1. Credit reseller account_balance with profit
     * 2. Record Income entry (Paid)
     * 3. Mark vendor earnings as available
     * 4. Create delivered comment/notification
     * 5. Send review prompts
     */
    public function markDelivered(Order $order): bool
    {
        // Guard: don't double-credit
        if ($this->hasDeliveryIncomeRecord($order)) {
            Log::info('OrderDeliveryService: skipping — income already recorded', [
                'order_id' => $order->id,
            ]);
            return false;
        }

        // 1. Credit reseller (atomic update to avoid stale-read race conditions)
        $user = User::find($order->user_id);
        if ($user) {
            \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update([
                'order_bonus' => \Illuminate\Support\Facades\DB::raw('order_bonus + ' . (float) $order->order_bonus),
                'sell_profit' => \Illuminate\Support\Facades\DB::raw('sell_profit + ' . (float) $order->profit),
                'account_balance' => \Illuminate\Support\Facades\DB::raw('account_balance + ' . (float) $order->profit),
            ]);
            $user->refresh();
        }

        // 2. Record Income
        $this->recordIncomePaid($order);

        // 3. Set delivery date
        if (!$order->deliveryDate) {
            $order->deliveryDate = now()->format('Y-m-d');
        }

        // 4. Mark vendor earnings available
        try {
            app(VendorCommissionService::class)->syncEarningsForOrder($order->id);
            app(VendorCommissionService::class)->markEarningsAvailableForOrder($order->id);
        } catch (\Throwable $e) {
            Log::warning('OrderDeliveryService: vendor earnings sync failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 5. Create delivered comment for the customer
        $this->createDeliveredComment($order);

        // 6. Send "Order Delivered" notification (non-blocking)
        if ($user) {
            $this->sendDeliveredNotification($order, $user);
        }

        // 7. Send review prompts (non-blocking)
        if ($user) {
            $this->sendReviewNotifications($order, $user);
        }

        // 7. Credit legacy shop account (for non-vendor store orders)
        $this->creditLegacyShopAccount($order);

        // 8. Notify vendor(s)/supplier(s) about successful delivery
        $this->notifyVendorsAboutDelivery($order);

        Log::info('OrderDeliveryService: order marked delivered', [
            'order_id' => $order->id,
            'invoice' => $order->invoiceID,
            'profit_credited' => $order->profit,
        ]);

        return true;
    }

    /**
     * Reverse delivery credits when an order's status is changed FROM Delivered.
     */
    public function reverseDelivery(Order $order): void
    {
        $user = User::find($order->user_id);
        if ($user) {
            \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update([
                'order_bonus' => \Illuminate\Support\Facades\DB::raw('GREATEST(0, order_bonus - ' . (float) $order->order_bonus . ')'),
                'sell_profit' => \Illuminate\Support\Facades\DB::raw('sell_profit - ' . (float) $order->profit),
                'account_balance' => \Illuminate\Support\Facades\DB::raw('account_balance - ' . (float) $order->profit),
            ]);
            $user->refresh();
        }

        $this->markIncomeCanceled($order);

        Log::info('OrderDeliveryService: delivery reversed', [
            'order_id' => $order->id,
            'profit_reversed' => $order->profit,
        ]);
    }

    /**
     * Refund delivery charge to reseller wallet on order cancellation/rejection.
     * Idempotent — safe to call multiple times for the same order.
     *
     * Called from:
     * - VendorOrderController (vendor rejects/cancels)
     * - OrderController (admin cancels — single or bulk)
     */
    public function refundDeliveryCharge(Order $order): bool
    {
        $deliveryCharge = (float) $order->deliveryCharge;

        // Guard: skip if no delivery charge to refund
        if ($deliveryCharge <= 0) {
            Log::info('OrderDeliveryService: skip refund — no delivery charge', [
                'order_id' => $order->id,
            ]);
            return false;
        }

        // Guard: don't double-refund — check for existing refund record
        $alreadyRefunded = Chargededuct::where('user_id', $order->user_id)
            ->where('comment', 'LIKE', '%refund%' . $order->invoiceID . '%')
            ->exists();

        if ($alreadyRefunded) {
            Log::info('OrderDeliveryService: skip refund — already refunded', [
                'order_id' => $order->id,
                'invoice' => $order->invoiceID,
            ]);
            return false;
        }

        $user = User::find($order->user_id);
        if (!$user) {
            Log::warning('OrderDeliveryService: skip refund — user not found', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ]);
            return false;
        }

        // Credit delivery charge back to wallet (atomic increment to avoid stale-read race conditions)
        $user->increment('account_balance', $deliveryCharge);
        $user->refresh(); // Reload to get the actual new balance for logging

        // Create audit record
        $chargededuct = new Chargededuct();
        $chargededuct->user_id = $user->id;
        $chargededuct->comment = 'Delivery charge refund of ৳' . $deliveryCharge . ' for cancelled order #' . $order->invoiceID;
        $chargededuct->amount = $deliveryCharge;
        $chargededuct->status = 'Refund';
        $chargededuct->save();

        // Send notification to reseller
        try {
            $user->notify(new AdminBroadcastNotification(
                '💰 Delivery Charge Refunded',
                '৳' . $deliveryCharge . ' delivery charge has been refunded to your wallet for cancelled order #' . $order->invoiceID,
                null,
                '/order/' . $order->invoiceID,
                'all_user',
                ['type' => 'delivery_refund', 'order_id' => $order->id, 'amount' => $deliveryCharge]
            ));
        } catch (\Throwable $e) {
            Log::warning('OrderDeliveryService: refund notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('OrderDeliveryService: delivery charge refunded', [
            'order_id' => $order->id,
            'invoice' => $order->invoiceID,
            'amount' => $deliveryCharge,
            'user_id' => $user->id,
            'new_balance' => $user->account_balance,
        ]);

        return true;
    }

    /**
     * Check if income has already been recorded for this order.
     * Prevents double-crediting on repeated syncs.
     */
    private function hasDeliveryIncomeRecord(Order $order): bool
    {
        $query = Income::where('from', 'Order');

        $query->where(function ($q) use ($order) {
            if (Schema::hasColumn('incomes', 'order_id')) {
                $q->where('order_id', $order->id);
            }
            if (Schema::hasColumn('incomes', 'invoice_code')) {
                $q->orWhere('invoice_code', (string) $order->invoiceID);
            }
            $q->orWhere('invoice_id', (int) $order->id);
        });

        return $query->where('status', 'Paid')->exists();
    }

    /**
     * Find existing income record for an order.
     */
    private function findIncomeRecord(Order $order): ?Income
    {
        $query = Income::where('from', 'Order');

        $query->where(function ($q) use ($order) {
            if (Schema::hasColumn('incomes', 'order_id')) {
                $q->where('order_id', $order->id);
            }
            if (Schema::hasColumn('incomes', 'invoice_code')) {
                $q->orWhere('invoice_code', (string) $order->invoiceID);
            }
            $q->orWhere('invoice_id', (int) $order->id);
        });

        return $query->latest('id')->first();
    }

    /**
     * Create or update Income record as Paid.
     */
    private function recordIncomePaid(Order $order): void
    {
        $income = $this->findIncomeRecord($order) ?? new Income();
        $income->from = 'Order';
        $income->invoice_id = (int) $order->id;
        $income->message = 'Congratulations ! you get ' . $order->profit . ' TK from Order : ' . $order->invoiceID;
        $income->amount = $order->profit;
        $income->user_id = $order->user_id;
        $income->status = 'Paid';

        if (Schema::hasColumn('incomes', 'order_id')) {
            $income->order_id = $order->id;
        }
        if (Schema::hasColumn('incomes', 'invoice_code')) {
            $income->invoice_code = (string) $order->invoiceID;
        }

        $income->save();
    }

    /**
     * Mark income record as canceled.
     */
    private function markIncomeCanceled(Order $order): void
    {
        $income = $this->findIncomeRecord($order);
        if (!$income) {
            return;
        }

        $income->message = 'Opps ! We deduct ' . $order->profit . ' TK for cancel Order : ' . $order->invoiceID;
        $income->status = 'Canceled';
        $income->save();
    }

    /**
     * Create a delivered comment visible to the customer.
     */
    private function createDeliveredComment(Order $order): void
    {
        // Check if delivered comment already exists
        $exists = Comment::where('order_id', $order->id)
            ->where('type', 'Delivered')
            ->exists();

        if ($exists) {
            return;
        }

        $comment = new Comment();
        $comment->order_id = $order->id;
        $comment->comment = 'Your order ' . $order->invoiceID . ' has been delivered successfully.';
        $comment->user_id = $order->user_id;
        $comment->status = 1;
        $comment->type = 'Delivered';
        $comment->save();
    }

    /**
     * Send "Order Delivered" bell notification to the user.
     */
    private function sendDeliveredNotification(Order $order, User $user): void
    {
        try {
            $user->notify(new AdminBroadcastNotification(
                '📦 Order Delivered',
                'Your order #' . $order->invoiceID . ' has been delivered successfully! ৳' . $order->profit . ' has been added to your balance.',
                null,
                '/order/' . $order->invoiceID,
                'all_user',
                ['type' => 'order_delivered', 'order_id' => $order->id]
            ));
        } catch (\Throwable $e) {
            Log::warning('OrderDeliveryService: delivered notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send review prompt notifications for each product in the order.
     */
    private function sendReviewNotifications(Order $order, User $user): void
    {
        try {
            $orderProducts = Orderproduct::where('order_id', $order->id)->get();
            foreach ($orderProducts as $op) {
                $product = Product::find($op->product_id);
                if (!$product) {
                    continue;
                }

                $user->notify(new AdminBroadcastNotification(
                    'Rate Your Product',
                    'Your order has been delivered! Please rate "' . $product->ProductName . '".',
                    $product->ViewProductImage ?? null,
                    '/product/' . $product->ProductSlug,
                    'all_user',
                    ['type' => 'review_prompt', 'product_id' => $product->id, 'order_id' => $order->id]
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('OrderDeliveryService: review notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Credit legacy shop (Admin) account for non-vendor stores.
     */
    private function creditLegacyShopAccount(Order $order): void
    {
        if ((int) $order->store_id === 1 || !$order->store_id) {
            return;
        }

        try {
            $orderProducts = Orderproduct::where('order_id', $order->id)->get();
            $wholesale = 0;
            foreach ($orderProducts as $opd) {
                $product = Product::find($opd->product_id);
                if ($product) {
                    $wholesale += (float) $product->ProductWholesalePrice * (int) $opd->quantity;
                }
            }

            if ($wholesale <= 0) {
                return;
            }

            $shop = \App\Models\Admin::find($order->store_id);
            if (!$shop) {
                return;
            }

            $wp = new \App\Models\Vencomment();
            $wp->order_id = $order->invoiceID;
            $wp->type = 'Deposit';
            $wp->comment = 'Congratulations ! you get ' . $order->profit . ' TK from Order : ' . $order->invoiceID;
            $wp->amount = $wholesale;
            $wp->blance = (float) $shop->account_balance + $wholesale;
            $wp->shop_id = $order->store_id;
            $wp->status = 'Paid';
            $wp->save();

            $shop->account_balance = (float) $shop->account_balance + $wholesale;
            $shop->save();
        } catch (\Throwable $e) {
            Log::warning('OrderDeliveryService: legacy shop credit failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify vendor(s)/supplier(s) that their products in this order have been delivered.
     */
    private function notifyVendorsAboutDelivery(Order $order): void
    {
        try {
            $vendorIds = Orderproduct::where('order_id', $order->id)
                ->join('products', 'orderproducts.product_id', '=', 'products.id')
                ->whereNotNull('products.vendor_id')
                ->distinct()
                ->pluck('products.vendor_id');

            if ($vendorIds->isEmpty()) {
                return;
            }

            $notificationService = app(VendorAdminNotificationService::class);
            foreach ($vendorIds as $vendorId) {
                $notificationService->notifyVendorById(
                    (int) $vendorId,
                    'Order Delivered',
                    'Order #' . $order->invoiceID . ' has been delivered successfully.',
                    'success',
                    [
                        'order_id' => $order->id,
                        'invoice_id' => $order->invoiceID,
                        'event' => 'order_delivered',
                    ],
                    '/vendor/orders'
                );
            }
        } catch (\Throwable $e) {
            Log::warning('OrderDeliveryService: vendor delivery notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
