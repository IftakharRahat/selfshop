<?php

namespace App\Services;

use App\Models\Comment;
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

        // 1. Credit reseller
        $user = User::find($order->user_id);
        if ($user) {
            $user->order_bonus = (float) $user->order_bonus + (float) $order->order_bonus;
            $user->sell_profit = (float) $user->sell_profit + (float) $order->profit;
            $user->account_balance = (float) $user->account_balance + (float) $order->profit;
            $user->save();
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
            $user->order_bonus = (float) $user->order_bonus - (float) $order->order_bonus;
            $user->sell_profit = (float) $user->sell_profit - (float) $order->profit;
            $user->account_balance = (float) $user->account_balance - (float) $order->profit;
            $user->save();
        }

        $this->markIncomeCanceled($order);

        Log::info('OrderDeliveryService: delivery reversed', [
            'order_id' => $order->id,
            'profit_reversed' => $order->profit,
        ]);
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
}
