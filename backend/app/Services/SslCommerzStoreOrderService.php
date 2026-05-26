<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Comment;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SslCommerzStoreOrderService
{
    public function ensureStoreOrders(int $parentOrderId, $cartData, array $context = [], array $options = []): array
    {
        $options = array_merge([
            'notify_suppliers' => true,
            'create_comments' => true,
            'decrement_stock' => true,
        ], $options);

        $parentOrder = Order::find($parentOrderId);
        if (!$parentOrder) {
            throw new \RuntimeException("Parent order #{$parentOrderId} not found.");
        }

        $items = $this->flattenCartItems($cartData);
        if (empty($items)) {
            return ['created_count' => 0, 'skipped' => true];
        }

        $resolvedContext = $this->resolveContext($parentOrder, $context);
        $groupedItems = $this->groupItemsByStore($items);
        $createdCount = 0;

        foreach ($groupedItems as $storeId => $storeItems) {
            if ($storeId <= 0) {
                Log::warning('Skipping SSLCommerz store order creation because store id is invalid.', [
                    'parent_order_id' => $parentOrderId,
                    'store_id' => $storeId,
                ]);
                continue;
            }

            $storeTransactionId = 'STORE_' . $parentOrderId . '_' . $storeId;
            $storeOrder = Order::where('transaction_id', $storeTransactionId)->first();

            if ($storeOrder) {
                $this->ensureStoreOrderCustomer($storeOrder->id, $resolvedContext);
                $this->ensureStoreOrderProducts($storeOrder->id, $storeItems);
                continue;
            }

            $storeOrder = $this->createStoreOrder($parentOrder, $storeId, $storeItems, $resolvedContext);
            $this->ensureStoreOrderCustomer($storeOrder->id, $resolvedContext);
            $vendorIds = $this->ensureStoreOrderProducts($storeOrder->id, $storeItems);

            if ($options['create_comments']) {
                $this->createStoreOrderComment($storeOrder);
            }

            if ($options['decrement_stock']) {
                try {
                    app(StockService::class)->decrementForOrder($storeOrder->id);
                } catch (\Throwable $e) {
                    Log::warning('Stock decrement failed for SSLCommerz store order', [
                        'order_id' => $storeOrder->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($options['notify_suppliers'] && !empty($vendorIds)) {
                try {
                    app(SupplierOrderNotificationService::class)->notify(
                        $storeOrder,
                        array_keys($vendorIds),
                        $resolvedContext['customer_name'] ?: null
                    );
                } catch (\Throwable $e) {
                    Log::warning('Supplier notification failed for SSLCommerz store order', [
                        'order_id' => $storeOrder->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $createdCount++;
        }

        return ['created_count' => $createdCount];
    }

    private function resolveContext(Order $parentOrder, array $context): array
    {
        $data = $this->decodeJson($parentOrder->data ?? null);
        $customer = Customer::where('order_id', $parentOrder->id)->first();

        return [
            'user_id' => $context['user_id']
                ?? $parentOrder->user_id
                ?? $data['user_id']
                ?? null,
            'customer_name' => $context['customer_name']
                ?? $context['customerName']
                ?? $data['customer_name']
                ?? $data['customerName']
                ?? ($customer->customerName ?? 'Customer'),
            'customer_phone' => $context['customer_phone']
                ?? $context['customerPhone']
                ?? $data['customer_phone']
                ?? $data['customerPhone']
                ?? ($customer->customerPhone ?? ''),
            'customer_address' => $context['customer_address']
                ?? $context['customerAddress']
                ?? $data['customer_address']
                ?? $data['customerAddress']
                ?? ($customer->customerAddress ?? ''),
            'delivery_charge' => $this->firstNumericValueAsFloat([
                $context['delivery_charge'] ?? null,
                $context['deliveryCharge'] ?? null,
                $parentOrder->deliveryCharge ?? null,
                $parentOrder->paymentAmount ?? null,
                $data['delivery_charge_per_shop'] ?? null,
                $data['delivery_charge'] ?? null,
                $data['deliveryCharge'] ?? null,
                $data['total_delivery_charge'] ?? null,
            ]),
        ];
    }

    private function groupItemsByStore(array $items): array
    {
        $groupedItems = [];

        foreach ($items as $item) {
            $storeId = (int) ($item['weight'] ?? 0);
            if (!isset($groupedItems[$storeId])) {
                $groupedItems[$storeId] = [];
            }

            $groupedItems[$storeId][] = $item;
        }

        return $groupedItems;
    }

    private function createStoreOrder(Order $parentOrder, int $storeId, array $items, array $context): Order
    {
        [$sellPrice, $buyPrice, $bonus] = $this->calculateStoreTotals($items);
        $profit = $sellPrice - $buyPrice;
        $admin = $this->resolveStoreAdmin($storeId);

        $storeOrder = new Order();
        $storeOrder->profit = $profit;
        $storeOrder->order_bonus = $bonus;
        $storeOrder->user_id = $context['user_id'];
        $storeOrder->courier_id = 26;
        $storeOrder->store_id = $storeId;
        $storeOrder->invoiceID = $this->uniqueInvoiceId();
        $storeOrder->subTotal = $sellPrice;
        $storeOrder->deliveryCharge = $context['delivery_charge'];
        $storeOrder->paymentAmount = $context['delivery_charge'];
        $storeOrder->payment_type_id = $parentOrder->payment_type_id ?: 6;

        if (Schema::hasColumn('orders', 'advance_delivery')) {
            $storeOrder->advance_delivery = 1;
        }

        if (Schema::hasColumn('orders', 'payment_status')) {
            $storeOrder->payment_status = 'Paid';
        }

        $storeOrder->transaction_id = 'STORE_' . $parentOrder->id . '_' . $storeId;
        $storeOrder->orderDate = $parentOrder->orderDate ?: date('Y-m-d');
        $storeOrder->admin_id = $admin->id ?? $storeId;

        if (Schema::hasColumn('orders', 'data')) {
            $parentData = $this->decodeJson($parentOrder->data ?? null);
            $storeOrder->data = json_encode(array_merge($parentData, [
                'payment_type' => 'SSLCommerz',
                'balance_from' => 'online_pay',
                'advance_delivery' => 'yes',
                'parent_order_id' => $parentOrder->id,
            ]));
        }

        $storeOrder->save();

        return $storeOrder;
    }

    private function calculateStoreTotals(array $items): array
    {
        $sellPrice = 0.0;
        $buyPrice = 0.0;
        $bonus = 0.0;

        foreach ($items as $item) {
            $productId = $this->resolveProductId($item);
            if (!$productId) {
                continue;
            }

            $product = Product::find($productId);
            if (!$product) {
                continue;
            }

            $quantity = (int) ($item['qty'] ?? $item['quantity'] ?? 1);
            $price = (float) ($item['price'] ?? 0);
            $options = $this->normalizeOptions($item['options'] ?? []);
            $sellingPrice = $this->firstNumericValueAsFloat([
                $options['selling_price'] ?? null,
                $item['selling_price'] ?? null,
                $price,
            ]);

            $sellPrice += $sellingPrice * $quantity;
            $buyPrice += (float) ($product->ProductResellerPrice ?? 0) * $quantity;
            $bonus += (float) ($product->reseller_bonus ?? 0);
        }

        return [$sellPrice, $buyPrice, $bonus];
    }

    private function resolveStoreAdmin(int $storeId): ?Admin
    {
        $admin = Admin::where('id', $storeId)
            ->where('status', 'Active')
            ->first();

        if ($admin) {
            return $admin;
        }

        return Admin::whereHas('roles', function ($query) {
            $query->where('name', 'Executive');
        })
            ->where('add_by', $storeId)
            ->where('status', 'Active')
            ->inRandomOrder()
            ->first();
    }

    private function ensureStoreOrderCustomer(int $orderId, array $context): void
    {
        if (Customer::where('order_id', $orderId)->exists()) {
            return;
        }

        $customer = new Customer();
        $customer->order_id = $orderId;
        $customer->customerName = (string) ($context['customer_name'] ?? 'Customer');
        $customer->customerPhone = (string) ($context['customer_phone'] ?? '');
        $customer->customerAddress = (string) ($context['customer_address'] ?? '');
        $customer->save();
    }

    private function ensureStoreOrderProducts(int $orderId, array $items): array
    {
        $vendorIds = [];

        foreach ($items as $item) {
            $productId = $this->resolveProductId($item);
            if (!$productId) {
                continue;
            }

            $options = $this->normalizeOptions($item['options'] ?? []);
            $productCode = (string) ($item['code'] ?? $options['code'] ?? '');
            $color = $item['color'] ?? $options['color'] ?? null;
            $size = $item['size'] ?? $options['size'] ?? null;

            if (Orderproduct::where('order_id', $orderId)
                ->where('product_id', $productId)
                ->where('productCode', $productCode)
                ->where(function ($query) use ($color) {
                    $color === null
                        ? $query->whereNull('color')
                        : $query->where('color', $color);
                })
                ->where(function ($query) use ($size) {
                    $size === null
                        ? $query->whereNull('size')
                        : $query->where('size', $size);
                })
                ->exists()) {
                $vendorId = Product::where('id', $productId)->value('vendor_id');
                if ($vendorId) {
                    $vendorIds[(int) $vendorId] = true;
                }
                continue;
            }

            $orderProduct = new Orderproduct();
            $orderProduct->order_id = $orderId;
            $orderProduct->product_id = $productId;
            $orderProduct->productCode = $productCode;
            $orderProduct->color = $color;
            $orderProduct->size = $size;
            $orderProduct->productName = (string) ($item['name'] ?? 'Product');
            $orderProduct->quantity = (int) ($item['qty'] ?? $item['quantity'] ?? 1);
            $orderProduct->productPrice = (float) ($item['price'] ?? 0);

            if (!empty($options['selling_price']) && $options['selling_price'] !== 'undefined') {
                $orderProduct->selling_price = (float) $options['selling_price'];
            }

            $orderProduct->save();

            $vendorId = Product::where('id', $productId)->value('vendor_id');
            if ($vendorId) {
                $vendorIds[(int) $vendorId] = true;
            }
        }

        return $vendorIds;
    }

    private function createStoreOrderComment(Order $order): void
    {
        if (Comment::where('order_id', $order->id)->exists()) {
            return;
        }

        $comment = new Comment();
        $comment->order_id = $order->id;
        $comment->comment = $order->invoiceID . ' Order Has Been Created for Store';
        $comment->admin_id = $order->admin_id;
        $comment->save();
    }

    private function flattenCartItems($cartData): array
    {
        if ($this->looksLikeCartItem($cartData)) {
            return [$this->normalizeItem($cartData)];
        }

        $items = [];
        $normalizedCart = $this->normalizeValue($cartData);

        foreach ($normalizedCart as $value) {
            $normalizedValue = $this->normalizeValue($value);

            if ($this->looksLikeCartItem($normalizedValue)) {
                $items[] = $this->normalizeItem($normalizedValue);
                continue;
            }

            foreach ($normalizedValue as $nested) {
                $normalizedItem = $this->normalizeValue($nested);
                if ($this->looksLikeCartItem($normalizedItem)) {
                    $items[] = $this->normalizeItem($normalizedItem);
                }
            }
        }

        return $items;
    }

    private function looksLikeCartItem($value): bool
    {
        $value = $this->normalizeValue($value);

        return isset($value['id']) || isset($value['product_id']);
    }

    private function normalizeItem($item): array
    {
        $item = $this->normalizeValue($item);
        $item['options'] = $this->normalizeOptions($item['options'] ?? []);

        return $item;
    }

    private function normalizeOptions($options): array
    {
        $options = $this->normalizeValue($options);
        return is_array($options) ? $options : [];
    }

    private function resolveProductId(array $item): ?int
    {
        return $this->firstPositiveInt([
            $item['product_id'] ?? null,
            $item['options']['product_id'] ?? null,
            $item['id'] ?? null,
        ]);
    }

    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            $decoded = json_decode(json_encode($value), true);
            return is_array($decoded) ? $decoded : [];
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeValue($value): array
    {
        return $this->decodeJson($value);
    }

    private function firstPositiveInt(array $values): ?int
    {
        foreach ($values as $value) {
            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    private function firstNumericValueAsFloat(array $values): float
    {
        foreach ($values as $value) {
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return 0.0;
    }

    private function uniqueInvoiceId(): string
    {
        $lastOrder = Order::latest('id')->first();
        $orderId = $lastOrder ? ((int) $lastOrder->id + 1) : 1;

        return 'SS00' . $orderId;
    }
}
