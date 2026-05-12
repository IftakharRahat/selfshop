<?php

namespace App\Services;

use App\Library\SslCommerz\SslCommerzNotification;
use App\Models\Cart as ResellerCart;
use App\Models\Comment;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SslCommerzOrderFinalizer
{
    public function finalizeFromCallback(Request $request, bool $validateWithGateway = false, bool $trustedGatewayStatus = false): array
    {
        $tranId = trim((string) $request->input('tran_id'));
        if ($tranId === '') {
            return [
                'success' => false,
                'message' => 'Missing transaction id.',
                'status_code' => 422,
            ];
        }

        $order = DB::table('orders')->where('transaction_id', $tranId)->first();
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Order not found for transaction id.',
                'status_code' => 404,
                'transaction_id' => $tranId,
            ];
        }

        $orderData = $this->decodeJson($order->data ?? null);
        $expectedAmount = $this->expectedPaymentAmount($order, $request, $orderData);

        if (!$this->isPaymentValid($request, $tranId, $expectedAmount, $validateWithGateway, $trustedGatewayStatus)) {
            $this->markPaymentFailed($order, $request, $orderData);

            return [
                'success' => false,
                'message' => 'Payment validation failed.',
                'status_code' => 422,
                'order_id' => $order->id,
                'transaction_id' => $tranId,
            ];
        }

        try {
            $result = DB::transaction(function () use ($request, $tranId, $expectedAmount) {
                $lockedOrder = DB::table('orders')
                    ->where('transaction_id', $tranId)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedOrder) {
                    throw new \RuntimeException('Order disappeared while finalizing SSLCommerz payment.');
                }

                $orderData = $this->decodeJson($lockedOrder->data ?? null);
                $cartData = $this->decodeJson($lockedOrder->cart ?? null);
                $valueDData = $this->decodeJson($request->input('value_d'));
                $customerData = $this->resolveCustomerData($orderData, $valueDData);
                $userId = $this->resolveUserId($lockedOrder, $request, $valueDData);
                $paidAmount = (float) $request->input('amount', $expectedAmount);
                $valId = $request->input('val_id');
                $previousStatus = (string) ($lockedOrder->status ?? '');

                $mergedData = array_merge($orderData, [
                    'payment_status' => 'Paid',
                    'payment_date' => now()->toDateTimeString(),
                    'paid_amount' => $paidAmount,
                    'user_id' => $userId,
                    'sslcommerz_tran_id' => $tranId,
                    'sslcommerz_val_id' => $valId,
                    'sslcommerz_status' => $request->input('status'),
                ]);

                $updates = [
                    'status' => $this->finalizedStatus($previousStatus),
                    'payment_type_id' => $lockedOrder->payment_type_id ?: 6,
                    'paymentAmount' => $paidAmount ?: $expectedAmount,
                    'updated_at' => now(),
                ];

                if ($userId) {
                    $updates['user_id'] = $userId;
                }

                if (Schema::hasColumn('orders', 'data')) {
                    $updates['data'] = json_encode($mergedData);
                }

                if (Schema::hasColumn('orders', 'payment_status')) {
                    $updates['payment_status'] = 'Paid';
                }

                DB::table('orders')->where('id', $lockedOrder->id)->update($updates);

                $createdCustomer = $this->ensureCustomer($lockedOrder->id, $customerData);
                $createdProducts = $this->ensureOrderProducts($lockedOrder->id, $cartData);

                if ($createdProducts) {
                    try {
                        app(StockService::class)->decrementForOrder($lockedOrder->id);
                    } catch (\Throwable $e) {
                        \Log::warning('Stock decrement failed during finalization for order #' . $lockedOrder->id, [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                if ($previousStatus === 'Pending Payment' && ($createdCustomer || $createdProducts)) {
                    $freshOrder = Order::find($lockedOrder->id);
                    if ($freshOrder) {
                        $this->notifyFinalizedOrder($freshOrder, $customerData);
                    }
                }

                $this->clearCheckedOutNativeCart($cartData, $userId);

                return [
                    'success' => true,
                    'message' => 'Order finalized successfully.',
                    'order_id' => $lockedOrder->id,
                    'transaction_id' => $tranId,
                    'status' => $updates['status'],
                    'created_customer' => $createdCustomer,
                    'created_products' => $createdProducts,
                ];
            });

            return array_merge(['status_code' => 200], $result);
        } catch (\Throwable $e) {
            Log::error('SSLCommerz order finalization failed', [
                'transaction_id' => $tranId,
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Order finalization failed. Please contact support.',
                'status_code' => 500,
                'order_id' => $order->id ?? null,
                'transaction_id' => $tranId,
            ];
        }
    }

    private function isPaymentValid(Request $request, string $tranId, float $expectedAmount, bool $validateWithGateway, bool $trustedGatewayStatus): bool
    {
        $status = strtoupper((string) $request->input('status'));
        $valId = $request->input('val_id');

        if (!in_array($status, ['VALID', 'VALIDATED'], true)) {
            return false;
        }

        if ($trustedGatewayStatus) {
            return true;
        }

        if (!$valId) {
            return false;
        }

        if (!$validateWithGateway) {
            return true;
        }

        try {
            $ssl = new SslCommerzNotification();
            return (bool) $ssl->orderValidate($request->all(), $tranId, $expectedAmount, 'BDT');
        } catch (\Throwable $e) {
            Log::warning('SSLCommerz gateway validation failed', [
                'transaction_id' => $tranId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function expectedPaymentAmount(object $order, Request $request, array $orderData): float
    {
        foreach (['paymentAmount', 'deliveryCharge'] as $field) {
            if (isset($order->{$field}) && is_numeric($order->{$field})) {
                return (float) $order->{$field};
            }
        }

        foreach (['total_delivery_charge', 'delivery_charge_per_shop', 'paid_amount'] as $field) {
            if (isset($orderData[$field]) && is_numeric($orderData[$field])) {
                return (float) $orderData[$field];
            }
        }

        $requestAmount = $request->input('amount');
        if (is_numeric($requestAmount)) {
            return (float) $requestAmount;
        }

        return 0.0;
    }

    private function markPaymentFailed(object $order, Request $request, array $orderData): void
    {
        if (!in_array((string) ($order->status ?? ''), ['Pending Payment', 'Pending'], true)) {
            return;
        }

        $orderData['payment_status'] = 'Failed';
        $orderData['validation_failed'] = true;
        $orderData['paid_amount'] = $request->input('amount', 0);

        $updates = [
            'status' => 'Failed',
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('orders', 'data')) {
            $updates['data'] = json_encode($orderData);
        }

        if (Schema::hasColumn('orders', 'payment_status')) {
            $updates['payment_status'] = 'Failed';
        }

        DB::table('orders')->where('id', $order->id)->update($updates);
    }

    private function finalizedStatus(string $previousStatus): string
    {
        if (in_array($previousStatus, ['Pending Payment', 'Failed', 'Processing', ''], true)) {
            return 'Pending';
        }

        return $previousStatus;
    }

    private function resolveUserId(object $order, Request $request, array $valueDData): ?int
    {
        $candidates = [
            $request->input('value_a'),
            $valueDData['user_id'] ?? null,
            $order->user_id ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        return null;
    }

    private function resolveCustomerData(array $orderData, array $valueDData): array
    {
        return [
            'name' => $orderData['customer_name']
                ?? $orderData['customerName']
                ?? $valueDData['customer_name']
                ?? $valueDData['customerName']
                ?? 'Customer',
            'phone' => $orderData['customer_phone']
                ?? $orderData['customerPhone']
                ?? $valueDData['customer_phone']
                ?? $valueDData['customerPhone']
                ?? '',
            'address' => $orderData['customer_address']
                ?? $orderData['customerAddress']
                ?? $valueDData['customer_address']
                ?? $valueDData['customerAddress']
                ?? '',
        ];
    }

    private function ensureCustomer(int $orderId, array $customerData): bool
    {
        if (Customer::where('order_id', $orderId)->exists()) {
            return false;
        }

        $customer = new Customer();
        $customer->order_id = $orderId;
        $customer->customerName = (string) ($customerData['name'] ?? 'Customer');
        $customer->customerPhone = (string) ($customerData['phone'] ?? '');
        $customer->customerAddress = (string) ($customerData['address'] ?? '');
        $customer->save();

        return true;
    }

    private function ensureOrderProducts(int $orderId, array $cartData): bool
    {
        if (Orderproduct::where('order_id', $orderId)->exists()) {
            return false;
        }

        $items = $this->flattenCartItems($cartData);
        if (empty($items)) {
            return false;
        }

        foreach ($items as $item) {
            $options = $this->arrayValue($item, 'options', []);
            if (is_string($options)) {
                $options = $this->decodeJson($options);
            }
            if (!is_array($options)) {
                $options = [];
            }

            $productId = $this->firstNumericValue([
                $item['product_id'] ?? null,
                $options['product_id'] ?? null,
                $item['id'] ?? null,
            ]);

            if (!$productId) {
                continue;
            }

            $product = Product::find($productId);
            $sellingPrice = $options['selling_price'] ?? ($item['selling_price'] ?? null);

            $orderProduct = new Orderproduct();
            $orderProduct->order_id = $orderId;
            $orderProduct->product_id = $productId;
            $productCode = $product ? ($product->ProductSku ?? '') : '';
            $productName = $product ? ($product->ProductName ?? 'Product') : 'Product';
            $productPrice = $product ? ($product->ProductResellerPrice ?? $product->ProductPrice ?? 0) : 0;

            $orderProduct->productCode = (string) ($item['code'] ?? $options['code'] ?? $productCode);
            $orderProduct->productName = (string) ($item['name'] ?? $productName);
            $orderProduct->quantity = (int) ($item['qty'] ?? $item['quantity'] ?? 1);
            $orderProduct->productPrice = (float) ($item['price'] ?? $productPrice);
            $orderProduct->color = $item['color'] ?? $options['color'] ?? null;
            $orderProduct->size = $item['size'] ?? $options['size'] ?? null;

            if ($sellingPrice && $sellingPrice !== 'undefined') {
                $orderProduct->selling_price = (float) $sellingPrice;
            }

            $orderProduct->save();
        }

        return Orderproduct::where('order_id', $orderId)->exists();
    }

    private function flattenCartItems(array $cartData): array
    {
        if ($this->looksLikeCartItem($cartData)) {
            return [$cartData];
        }

        $items = [];

        foreach ($cartData as $value) {
            $normalized = $this->normalizeValue($value);
            if ($this->looksLikeCartItem($normalized)) {
                $items[] = $normalized;
                continue;
            }

            if (is_array($normalized)) {
                foreach ($normalized as $nested) {
                    $nestedItem = $this->normalizeValue($nested);
                    if ($this->looksLikeCartItem($nestedItem)) {
                        $items[] = $nestedItem;
                    }
                }
            }
        }

        return $items;
    }

    private function looksLikeCartItem($value): bool
    {
        return is_array($value)
            && (isset($value['product_id']) || isset($value['id']))
            && (isset($value['qty']) || isset($value['quantity']));
    }

    private function clearCheckedOutNativeCart(array $cartData, ?int $userId): void
    {
        if (!$userId) {
            return;
        }

        $cartIds = collect($this->flattenCartItems($cartData))
            ->filter(fn ($item) => isset($item['product_id']) && isset($item['id']) && is_numeric($item['id']))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($cartIds->isEmpty()) {
            return;
        }

        ResellerCart::where('user_id', $userId)
            ->whereIn('id', $cartIds->all())
            ->delete();
    }

    private function notifyFinalizedOrder(Order $order, array $customerData): void
    {
        try {
            if (!Comment::where('order_id', $order->id)->exists()) {
                $comment = new Comment();
                $comment->order_id = $order->id;
                $comment->comment = $order->invoiceID . ' Order has been created after SSLCommerz payment.';
                $comment->admin_id = $order->admin_id;
                $comment->save();
            }
        } catch (\Throwable $e) {
            Log::warning('SSLCommerz finalizer comment creation failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        $vendorIds = Orderproduct::where('order_id', $order->id)
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->map(fn ($productId) => Product::where('id', $productId)->value('vendor_id'))
            ->filter()
            ->map(fn ($vendorId) => (int) $vendorId)
            ->unique()
            ->values()
            ->all();

        foreach ($vendorIds as $vendorId) {
            try {
                $vendorNotificationMessage = "Order #{$order->invoiceID}. Please check and confirm for processing. Thanks, SelfShop Limited.";
                app(VendorAdminNotificationService::class)->notifyVendorById(
                    $vendorId,
                    'New Order Received!',
                    $vendorNotificationMessage,
                    'info',
                    [
                        'event' => 'vendor_order_created',
                        'order_id' => $order->id,
                        'invoiceID' => $order->invoiceID,
                    ],
                    '/vendor/orders/' . $order->id
                );
            } catch (\Throwable $e) {
                Log::warning('SSLCommerz finalizer vendor notification failed', ['order_id' => $order->id, 'vendor_id' => $vendorId, 'error' => $e->getMessage()]);
            }
        }

        try {
            app(SupplierOrderNotificationService::class)->notify($order, $vendorIds, $customerData['name'] ?? null);
        } catch (\Throwable $e) {
            Log::warning('SSLCommerz finalizer supplier notification failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        try {
            app(PushNotificationService::class)->onNewOrder($order);
        } catch (\Throwable $e) {
            Log::warning('SSLCommerz finalizer push notification failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return $this->normalizeValue($value);
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeValue($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            $decoded = json_decode(json_encode($value), true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function arrayValue(array $array, string $key, $default = null)
    {
        return array_key_exists($key, $array) ? $array[$key] : $default;
    }

    private function firstNumericValue(array $values): ?int
    {
        foreach ($values as $value) {
            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }
}
