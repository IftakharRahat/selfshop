<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshProductAggregation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:refresh-aggregation {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh aggregated quantity and minimum price for products';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('id');
        $query = \App\Models\Product::whereHas('varients');

        if ($id) {
            $query->where('id', $id);
        }

        $this->info("Starting product aggregation refresh...");

        $query->chunk(100, function ($products) {
            foreach ($products as $product) {
                $totalQty = 0;
                $minPrice = null;
                $firstSizePrice = null;

                $variants = $product->varients()->with(['sizes.bulkPrices'])->get();
                
                foreach ($variants as $index => $variant) {
                    if ($index === 0) {
                        if ($variant->sizes->isEmpty()) {
                            $vPrice = (float) ($variant->price ?: 0);
                            if ($vPrice > 0) {
                                $firstSizePrice = $vPrice;
                            }
                        } else {
                            $firstSize = $variant->sizes->first();
                            if ($firstSize) {
                                $sPrice = (float) ($firstSize->price ?: 0);
                                $bP = 0;
                                if ($firstSize->bulkPrices && $firstSize->bulkPrices->isNotEmpty()) {
                                    $bP = (float) ($firstSize->bulkPrices->first()->bulk_price ?: 0);
                                }
                                if ($sPrice > 0) {
                                    $firstSizePrice = $sPrice;
                                } elseif ($bP > 0) {
                                    $firstSizePrice = $bP;
                                }
                            }
                        }
                    }

                    if ($variant->sizes->isEmpty()) {
                        $totalQty += (int) $variant->qty;
                        $vPrice = (float) ($variant->price ?: 0);
                        if ($vPrice > 0 && ($minPrice === null || $vPrice < $minPrice)) {
                            $minPrice = $vPrice;
                        }
                    } else {
                        foreach ($variant->sizes as $size) {
                            $totalQty += (int) $size->qty;
                            
                            $pricesToCompare = [];
                            $sPrice = (float) ($size->price ?: 0);
                            if ($sPrice > 0) {
                                $pricesToCompare[] = $sPrice;
                            }
                            
                            // Check bulk prices
                            if ($size->bulkPrices && $size->bulkPrices->isNotEmpty()) {
                                foreach ($size->bulkPrices as $bp) {
                                    $bpPrice = (float) ($bp->bulk_price ?: 0);
                                    if ($bpPrice > 0) {
                                        $pricesToCompare[] = $bpPrice;
                                    }
                                }
                            }

                            if (!empty($pricesToCompare)) {
                                $bestSizePrice = min($pricesToCompare);
                                if ($minPrice === null || $bestSizePrice < $minPrice) {
                                    $minPrice = $bestSizePrice;
                                }
                            }
                        }
                    }
                }

                $product->qty = $totalQty;
                if ($minPrice !== null && $minPrice > 0) {
                    $product->ProductResellerPrice = $minPrice;
                }

                if ($firstSizePrice !== null && $firstSizePrice > 0) {
                    $product->ProductRegularPrice = $firstSizePrice;
                } elseif ($minPrice !== null && $minPrice > 0) {
                    $product->ProductRegularPrice = $minPrice;
                }

                $product->save();
                $this->line("Refreshed Product ID: {$product->id} - Qty: {$totalQty}, Price: ৳{$minPrice}, Reg: ৳{$firstSizePrice}");
            }
        });

        $this->info("Finished product aggregation refresh.");
        return 0;
    }
}
