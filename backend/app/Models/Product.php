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
        return $query->where('status', 'Active')
            ->where(function ($q) {
                $q->whereNull('vendor_id')
                    ->orWhere('vendor_approval_status', 'approved');
            });
    }
}
