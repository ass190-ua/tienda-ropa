<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'description_short',
        'description_long',
        'price',
        'color_id',
        'size_id',
        'category_id',
        'type_id',
        'discount_type',
        'discount_value',
        'discount_start',
        'discount_end',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_start' => 'datetime',
        'discount_end' => 'datetime',
    ];

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function stockItems()
    {
        return $this->hasMany(StockItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderLines()
    {
        return $this->hasMany(OrderLine::class);
    }

    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function color()
    {
        return $this->belongsTo(AttributeValue::class, 'color_id');
    }

    public function size()
    {
        return $this->belongsTo(AttributeValue::class, 'size_id');
    }

    public function category()
    {
        return $this->belongsTo(AttributeValue::class, 'category_id');
    }

    public function type()
    {
        return $this->belongsTo(AttributeValue::class, 'type_id');
    }
}
