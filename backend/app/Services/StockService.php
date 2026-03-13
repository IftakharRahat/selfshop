<?php

namespace App\Services;

use App\Models\Orderproduct;
use App\Models\Product;
use App\Models\Varient;
use App\Models\VariantSize;
use Illuminate\Support\Facades\Log;

class StockService
{
    /**
     * Decrement stock for all products in an order.
     *
     * Reduces:
     *  - variant_sizes.qty  (if color + size match)
     *  - varients.qty       (if color matches)
     *  - products.ProductQuantity
     */
    public function decrementForOrder(int $orderId): void
    {
        $orderProducts = Orderproduct::where('order_id', $orderId)->get();

        foreach ($orderProducts as $op) {
            $this->adjustStock($op, 'decrement');
        }
    }

    /**
     * Restore stock for all products in an order (on cancel / reject / return).
     *
     * Increments all levels back by the ordered quantity.
     */
    public function restoreForOrder(int $orderId): void
    {
        $orderProducts = Orderproduct::where('order_id', $orderId)->get();

        foreach ($orderProducts as $op) {
            $this->adjustStock($op, 'restore');
        }
    }

    /**
     * Core stock adjustment logic shared by decrement and restore.
     *
     * @param Orderproduct $op
     * @param string       $direction  'decrement' | 'restore'
     */
    private function adjustStock(Orderproduct $op, string $direction): void
    {
        $qty = (int) $op->quantity;
        if ($qty <= 0) {
            return;
        }

        $colorName = $op->color;
        $sizeName  = $op->size;

        // --- Variant-level stock ---
        if ($colorName && $colorName !== 'undefined') {
            $variant = Varient::where('product_id', $op->product_id)
                ->where('color_name', $colorName)
                ->first();

            if ($variant) {
                // Size-level stock
                if ($sizeName && $sizeName !== 'undefined') {
                    $variantSize = VariantSize::where('varient_id', $variant->id)
                        ->where('size_name', $sizeName)
                        ->first();

                    if ($variantSize) {
                        $variantSize->qty = $direction === 'decrement'
                            ? max(0, $variantSize->qty - $qty)
                            : $variantSize->qty + $qty;
                        $variantSize->save();
                    }
                }

                // Variant (color) stock
                $variant->qty = $direction === 'decrement'
                    ? max(0, $variant->qty - $qty)
                    : $variant->qty + $qty;
                $variant->save();
            }
        }

        // --- Product-level stock ---
        $product = Product::find($op->product_id);
        if ($product && $product->ProductQuantity !== null) {
            $product->ProductQuantity = $direction === 'decrement'
                ? max(0, $product->ProductQuantity - $qty)
                : $product->ProductQuantity + $qty;
            $product->save();
        }
    }
}
