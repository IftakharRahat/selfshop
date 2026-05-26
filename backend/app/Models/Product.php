<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasSlug;
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['storefront_price'];

    protected $casts = [
        'warranty_days' => 'integer',
    ];

    /**
     * Commission-inclusive display price for resellers.
     * Uses ProductResellerPrice (or ProductSalePrice / ProductRegularPrice fallback)
     * multiplied by the vendor commission factor.
     */
    public function getStorefrontPriceAttribute(): ?float
    {
        $base = (float) ($this->ProductResellerPrice ?: 0);

        // If base price is 0, prefer aggregated/storefront fields.
        if ($base <= 0) {
            $base = (float) ($this->min_sell_price
                ?? $this->ProductSalePrice
                ?? $this->ProductRegularPrice
                ?? 0);
        }

        // If product-level fields are still 0, derive the lowest positive variant/size price.
        if ($base <= 0) {
            $base = (float) ($this->resolveVariantBasePrice() ?? 0);
        }

        if ($base <= 0 || !$this->vendor_id) {
            return $base > 0 ? $base : null;
        }

        $service = app(\App\Services\VendorCommissionService::class);
        return $service->getStorefrontPrice($base, $this->vendor_id, $this->category_id);
    }

    protected function resolveVariantBasePrice(): ?float
    {
        $prices = [];

        if ($this->relationLoaded('varients')) {
            foreach ($this->varients as $variant) {
                $variantPrice = (float) ($variant->price ?? 0);
                if ($variantPrice > 0) {
                    $prices[] = $variantPrice;
                }

                if ($variant->relationLoaded('sizes')) {
                    foreach ($variant->sizes as $size) {
                        $sizePrice = (float) ($size->price ?? 0);
                        if ($sizePrice > 0) {
                            $prices[] = $sizePrice;
                        }
                    }
                }
            }
        } else {
            $variantIds = $this->varients()->pluck('id');

            $variantMin = $this->varients()
                ->where('price', '>', 0)
                ->min('price');
            if ($variantMin > 0) {
                $prices[] = (float) $variantMin;
            }

            if ($variantIds->isNotEmpty()) {
                $sizeMin = \App\Models\VariantSize::whereIn('varient_id', $variantIds)
                    ->where('price', '>', 0)
                    ->min('price');
                if ($sizeMin > 0) {
                    $prices[] = (float) $sizeMin;
                }
            }
        }

        if (empty($prices)) {
            return null;
        }

        return min($prices);
    }

    public function categories()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategories()
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id');
    }

    public function minicategories()
    {
        return $this->belongsTo(Minicategory::class, 'minicategory_id');
    }

    public function varients()
    {
        return $this->hasMany(Varient::class);
    }

    public function priceTiers()
    {
        return $this->hasMany(ProductPriceTier::class)->orderBy('min_qty');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('ProductName')
            ->saveSlugsTo('ProductSlug');
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyNameCategory()
    {
        return 'ProductSlug';
    }

    /**
     * Scope: products visible on storefront (Active + vendor products only if approved).
     */
    public function scopeVisibleOnStorefront($query)
    {
        return $query->where('products.status', 'Active')
            ->where(function ($q) {
                $q->whereNull('products.vendor_id')
                    ->orWhere('products.vendor_approval_status', 'approved');
            })
            ->where(function ($q) {
                // Hide stock-out products (frature = 0 means out of stock)
                $q->where('products.frature', '!=', 0)
                    ->orWhereNull('products.frature');
            });
    }
}
