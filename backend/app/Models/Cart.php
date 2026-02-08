<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'code',
        'price',
        'color',
        'size',
        'qty',
        'shop_id',
        'image',
        'options',
        'session_id',
        'user_id',
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
