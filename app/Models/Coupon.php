<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    const TYPE_PERCENT = 'percent';
    const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'min_order_total',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_total' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

