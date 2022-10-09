<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    public function productVariantPrices()
    {
        return $this->hasMany(ProductVariantPrice::class);
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function productImage()
    {
        return $this->hasMany(ProductImage::class);
    }
}
