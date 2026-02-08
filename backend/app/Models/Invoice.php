<?php

// Create the Resellerinvoice model
// app/Models/Resellerinvoice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resellerinvoice extends Model
{
    use HasFactory;

    protected $table = 'resellerinvoices'; // Important: specify table name
    
    protected $fillable = [
        'invoiceID',
        'package_id',
        'user_id',
        'resellerid',
        'blocking_reason',
        'from_date',
        'to_date',
        'amount',
        'bonus_percent',
        'discount',
        'payable_amount',
        'paid_amount',
        'payment_id', // CRITICAL: This must be here
        'payment_type',
        'invoiceDate',
        'paymentDate',
        'status',
        'expire_date',
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'invoiceDate' => 'date',
        'paymentDate' => 'date',
        'from_date' => 'date',
        'to_date' => 'date',
    ];
    
    // Relationship with user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    // Relationship with package
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}