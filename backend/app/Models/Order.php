<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
protected $casts = [
    'profit'       => 'float',
    'order_bonus'  => 'float',
    'subTotal'     => 'integer',
    'deliveryCharge' => 'integer',
    'discountCharge' => 'integer',
    'paymentAmount' => 'integer',
];
    // Your existing relationships...
    public function orderproducts()
    {
        return $this->hasMany(Orderproduct::class, 'order_id');
    }

    public function comments()
    {
        return $this->hasOne('App\Models\Comment', 'order_id', 'id')->latest();
    }

    public function customers()
    {
        return $this->hasOne(Customer::class, 'order_id');
    }

    public function admins()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function couriers()
    {
        return $this->belongsTo(Courier::class, 'courier_id');
    }

    public function products()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function cities()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function zones()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }
}