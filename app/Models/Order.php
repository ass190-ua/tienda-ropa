<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    const STATUS_PENDING   = 'pending';
    const STATUS_PAID      = 'paid';
    const STATUS_SHIPPED   = 'shipped';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'address_id',
        'coupon_id',
        'subtotal',
        'discount_total',
        'coupon_discount_total',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'coupon_discount_total' => 'decimal:2',
    ];

    protected $appends = ['total'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function lines()
    {
        return $this->hasMany(OrderLine::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getTotalAttribute()
    {
        // 1. Si hay pagos, el total real es lo que se pagó (incluye envío)
        // Usamos la relación cargada o la cargamos si no existe
        $latestPayment = $this->relationLoaded('payments')
            ? $this->payments->sortByDesc('id')->first()
            : $this->payments()->orderByDesc('id')->first();

        if ($latestPayment) {
            return (float) $latestPayment->amount;
        }

        // 2. Si no hay pagos (ej. pendiente), calculamos base
        $base = (float)$this->subtotal
               - (float)$this->discount_total
               - (float)$this->coupon_discount_total;

        return max(0, $base);
    }
}

class OrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
