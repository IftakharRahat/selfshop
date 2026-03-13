<?php

namespace App\Services;

use App\Mail\NewOrderForSupplier;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SupplierOrderNotificationService
{
    protected BulkSmsService $sms;

    public function __construct(BulkSmsService $sms)
    {
        $this->sms = $sms;
    }

    /**
     * Send SMS + Email notifications to each vendor whose products appear in the order.
     *
     * @param  Order     $order
     * @param  int[]     $vendorIds   Unique vendor IDs involved in this order
     * @param  string|null $customerName  For display in messages
     */
    public function notify(Order $order, array $vendorIds, ?string $customerName = null): void
    {
        if (empty($vendorIds)) {
            return;
        }

        foreach ($vendorIds as $vendorId) {
            try {
                $vendor = Vendor::find($vendorId);
                if (!$vendor) {
                    continue;
                }

                // Get this vendor's products in the order
                $vendorProductIds = Product::where('vendor_id', $vendorId)->pluck('id')->toArray();
                $orderProducts = Orderproduct::where('order_id', $order->id)
                    ->whereIn('product_id', $vendorProductIds)
                    ->get();

                if ($orderProducts->isEmpty()) {
                    continue;
                }

                // ── SMS ──
                $this->sendSms($vendor, $order, $orderProducts);

                // ── Email ──
                $this->sendEmail($vendor, $order, $orderProducts, $customerName);

            } catch (\Throwable $e) {
                Log::warning('SupplierOrderNotificationService: Failed for vendor', [
                    'vendor_id' => $vendorId,
                    'order_id'  => $order->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }
    }

    protected function sendSms(Vendor $vendor, Order $order, $orderProducts): void
    {
        $phone = $vendor->contact_phone;
        if (empty($phone)) {
            Log::info('SupplierOrderNotificationService: No contact_phone for vendor', ['vendor_id' => $vendor->id]);
            return;
        }

        $productSummary = $orderProducts->map(function ($item) {
            return $item->productName . ' x' . $item->quantity;
        })->implode(', ');

        // Keep SMS short (160 chars ideal)
        $message = "SelfShop: New order {$order->invoiceID}! Products: {$productSummary}. Please check your supplier dashboard.";

        // Truncate if too long
        if (strlen($message) > 320) {
            $message = "SelfShop: New order {$order->invoiceID} with {$orderProducts->count()} product(s). Please check your supplier dashboard.";
        }

        try {
            $this->sms->send($phone, $message);
        } catch (\Throwable $e) {
            Log::warning('SupplierOrderNotificationService: SMS failed', [
                'vendor_id' => $vendor->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    protected function sendEmail(Vendor $vendor, Order $order, $orderProducts, ?string $customerName): void
    {
        $email = $vendor->contact_email;
        if (empty($email)) {
            Log::info('SupplierOrderNotificationService: No contact_email for vendor', ['vendor_id' => $vendor->id]);
            return;
        }

        try {
            Mail::to($email)->send(new NewOrderForSupplier($order, $vendor, $orderProducts, $customerName));

            Log::info('SupplierOrderNotificationService: Email sent', [
                'vendor_id' => $vendor->id,
                'email'     => $email,
                'order_id'  => $order->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('SupplierOrderNotificationService: Email failed', [
                'vendor_id' => $vendor->id,
                'email'     => $email,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
