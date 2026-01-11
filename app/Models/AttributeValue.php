<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'attribute_id',
        'value',
    ];
    
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function productsAsColor()
    {
        return $this->hasMany(Product::class, 'color_id');
    }

    public function productsAsSize()
    {
        return $this->hasMany(Product::class, 'size_id');
    }

    public function productsAsBrand()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    public function productsAsType()
    {
        return $this->hasMany(Product::class, 'type_id');
    }

    public function productsAsCategory()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
