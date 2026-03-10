<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionalSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'banner_image',
        'layout_type',
        'bg_color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Products belonging to this promotional section.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'promotional_section_products', 'section_id', 'product_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order')
            ->withTimestamps();
    }

    /**
     * Scope: only active sections.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
