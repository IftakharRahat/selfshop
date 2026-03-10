<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Fix existing SSLCommerz orders that have wrong status and no customer
$orders = DB::table('orders')
    ->where('status', 'Processing')
    ->where('transaction_id', 'LIKE', 'SELFSHOP_%')
    ->get();

echo "Found " . $orders->count() . " SSLCommerz orders with Processing status\n\n";

foreach ($orders as $order) {
    echo "Fixing Order #{$order->id} ({$order->invoiceID}):\n";
    
    // Fix status to Pending
    DB::table('orders')->where('id', $order->id)->update(['status' => 'Pending']);
    echo "  Status: Processing -> Pending\n";
    
    // Check if customer exists
    $customer = DB::table('customers')->where('order_id', $order->id)->first();
    if (!$customer) {
        // Try to get customer data from the 'data' JSON field
        $data = json_decode($order->data ?? '{}', true);
        $name = $data['customer_name'] ?? null;
        $phone = $data['customer_phone'] ?? null;
        $address = $data['customer_address'] ?? null;
        
        if ($name || $phone) {
            DB::table('customers')->insert([
                'order_id' => $order->id,
                'customerName' => $name ?? 'Customer',
                'customerPhone' => $phone ?? '',
                'customerAddress' => $address ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "  Created customer: {$name} | {$phone}\n";
        } else {
            echo "  No customer data in order JSON to recover\n";
        }
    } else {
        echo "  Customer already exists: {$customer->customerName}\n";
    }
    
    // Check order products
    $opsCount = DB::table('orderproducts')->where('order_id', $order->id)->count();
    if ($opsCount == 0 && $order->cart) {
        // Create order products from cart data
        $cartData = json_decode($order->cart, true);
        if (is_array($cartData)) {
            foreach ($cartData as $key => $item) {
                // Handle nested structure (items might be in sub-arrays)
                if (is_array($item) && !isset($item['id'])) {
                    // It's a grouping - iterate sub items
                    foreach ($item as $subItem) {
                        if (is_array($subItem) && isset($subItem['id'])) {
                            DB::table('orderproducts')->insert([
                                'order_id' => $order->id,
                                'product_id' => $subItem['id'],
                                'productName' => $subItem['name'] ?? 'Product',
                                'quantity' => $subItem['qty'] ?? 1,
                                'productPrice' => $subItem['price'] ?? 0,
                                'productCode' => $subItem['options']['code'] ?? '',
                                'color' => $subItem['options']['color'] ?? null,
                                'size' => $subItem['options']['size'] ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            echo "  Created orderproduct: {$subItem['name']}\n";
                        }
                    }
                } elseif (is_array($item) && isset($item['id'])) {
                    DB::table('orderproducts')->insert([
                        'order_id' => $order->id,
                        'product_id' => $item['id'],
                        'productName' => $item['name'] ?? 'Product',
                        'quantity' => $item['qty'] ?? 1,
                        'productPrice' => $item['price'] ?? 0,
                        'productCode' => $item['options']['code'] ?? '',
                        'color' => $item['options']['color'] ?? null,
                        'size' => $item['options']['size'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    echo "  Created orderproduct: {$item['name']}\n";
                }
            }
        }
    } elseif ($opsCount > 0) {
        echo "  Already has {$opsCount} order products\n";
    }
    echo "\n";
}

echo "Done!\n";
