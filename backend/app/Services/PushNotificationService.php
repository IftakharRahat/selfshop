<?php

namespace App\Services;

use App\Events\OrderNotificationEvent;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Notify a specific user by their user_id.
     */
    public function notifyUser(int $userId, string $title, string $message, string $type = 'info', array $meta = []): void
    {
        try {
            event(new OrderNotificationEvent($userId, $title, $message, $type, $meta));
        } catch (\Throwable $e) {
            Log::warning('PushNotificationService: broadcast failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify vendor(s) whose products are in this order.
     */
    public function notifyOrderVendors(int $orderId, string $title, string $message, string $type = 'info', array $extraMeta = []): void
    {
        try {
            $vendorUserIds = Orderproduct::where('order_id', $orderId)
                ->join('products', 'orderproducts.product_id', '=', 'products.id')
                ->whereNotNull('products.vendor_id')
                ->join('vendors', 'products.vendor_id', '=', 'vendors.id')
                ->join('users', 'vendors.user_id', '=', 'users.id')
                ->distinct()
                ->pluck('users.id');

            foreach ($vendorUserIds as $userId) {
                $this->notifyUser($userId, $title, $message, $type, array_merge(['order_id' => $orderId], $extraMeta));
            }
        } catch (\Throwable $e) {
            Log::warning('PushNotificationService: vendor notification failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify the reseller (order owner).
     */
    public function notifyReseller(Order $order, string $title, string $message, string $type = 'info', array $extraMeta = []): void
    {
        if (!$order->user_id) {
            return;
        }

        $this->notifyUser(
            (int) $order->user_id,
            $title,
            $message,
            $type,
            array_merge(['order_id' => $order->id, 'invoiceID' => $order->invoiceID], $extraMeta)
        );
    }

    /**
     * Notify all admin users.
     */
    public function notifyAdmins(string $title, string $message, string $type = 'info', array $meta = []): void
    {
        try {
            // Admins login via admin guard, but we broadcast on user channels.
            // For admin notifications, we broadcast on a public admin channel instead.
            event(new OrderNotificationEvent(0, $title, $message, $type, $meta));
        } catch (\Throwable $e) {
            Log::warning('PushNotificationService: admin notification failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // --- Convenience methods for common events ---

    public function onNewOrder(Order $order): void
    {
        $invoiceID = $order->invoiceID;

        // Notify vendor(s)
        $this->notifyOrderVendors(
            $order->id,
            '🛒 New Order Received',
            "New order #{$invoiceID} has been placed.",
            'success',
            ['event' => 'new_order']
        );

        // Notify admins
        $this->notifyAdmins(
            '🛒 New Order',
            "Order #{$invoiceID} placed by user #{$order->user_id}.",
            'info',
            ['order_id' => $order->id, 'event' => 'new_order']
        );
    }

    public function onStatusChange(Order $order, string $newStatus): void
    {
        $invoiceID = $order->invoiceID;

        // Notify reseller
        $this->notifyReseller(
            $order,
            "📦 Order Update: {$newStatus}",
            "Your order #{$invoiceID} status changed to {$newStatus}.",
            'info',
            ['event' => 'status_change', 'status' => $newStatus]
        );

        // Notify vendor(s)
        $this->notifyOrderVendors(
            $order->id,
            "📦 Order Status: {$newStatus}",
            "Order #{$invoiceID} status changed to {$newStatus}.",
            'info',
            ['event' => 'status_change', 'status' => $newStatus]
        );
    }

    public function onCourierAssigned(Order $order, string $courierName, ?string $trackingCode = null): void
    {
        $invoiceID = $order->invoiceID;
        $trackingMsg = $trackingCode ? " Tracking: {$trackingCode}" : '';

        // Notify reseller
        $this->notifyReseller(
            $order,
            '🚚 Order Shipped',
            "Your order #{$invoiceID} is on the way!{$trackingMsg}",
            'success',
            ['event' => 'courier_assigned', 'tracking_number' => $trackingCode]
        );

        // Notify vendor(s)
        $this->notifyOrderVendors(
            $order->id,
            "🚚 Courier Assigned: {$courierName}",
            "Order #{$invoiceID} assigned to {$courierName}.{$trackingMsg}",
            'info',
            ['event' => 'courier_assigned', 'tracking_number' => $trackingCode]
        );
    }

    public function onVendorAction(Order $order, string $action): void
    {
        $invoiceID = $order->invoiceID;
        $label = $action === 'accept' ? 'Accepted' : 'Cancelled';
        $icon = $action === 'accept' ? '✅' : '❌';

        // Notify reseller
        $this->notifyReseller(
            $order,
            "{$icon} Order {$label}",
            "Your order #{$invoiceID} has been {$label} by vendor.",
            $action === 'accept' ? 'success' : 'warning',
            ['event' => 'vendor_action', 'action' => $action]
        );

        // Notify admins
        $this->notifyAdmins(
            "{$icon} Vendor {$label} Order",
            "Order #{$invoiceID} was {$label} by vendor.",
            'info',
            ['order_id' => $order->id, 'event' => 'vendor_action', 'action' => $action]
        );
    }

    public function onWarehouseSent(Order $order): void
    {
        $invoiceID = $order->invoiceID;

        // Notify reseller
        $this->notifyReseller(
            $order,
            '📦 Order Sent to Warehouse',
            "Your order #{$invoiceID} has been sent to warehouse for delivery.",
            'info',
            ['event' => 'warehouse_sent']
        );

        // Notify admins
        $this->notifyAdmins(
            '📦 Warehouse Dispatch',
            "Order #{$invoiceID} sent to warehouse by vendor.",
            'info',
            ['order_id' => $order->id, 'event' => 'warehouse_sent']
        );
    }
}
