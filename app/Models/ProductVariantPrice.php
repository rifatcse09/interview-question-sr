<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    protected $guarded = [];

    public static function createVariantPrice($variantPrices, $variants, $productId)
    {
        $varientPrices   = [];

        // generate variant prices
        foreach ($variantPrices as $productVariant) {
            $priceVariants    = explode('/', $productVariant['title']);
            $productVariantOne = !empty($priceVariants[0]) ? $variants->where('variant', $priceVariants[0])->first()->id : null;
            $productVariantTwo = !empty($priceVariants[1]) ? $variants->where('variant', $priceVariants[1])->first()->id : null;
            $productVariantThree = !empty($priceVariants[2]) ? $variants->where('variant', $priceVariants[2])->first()->id : null;

            $varientPrices[]  = ['product_variant_one' => $productVariantOne, 'product_variant_two' => $productVariantTwo, 'product_variant_three' => $productVariantThree, 'price' => $productVariant['price'], 'stock' => $productVariant['stock'], 'product_id' => $productId];
        }
        return  $varientPrices;
    }

    public function variantOne()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_one', 'id');
    }

    public function variantTwo()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_two', 'id');
    }

    public function variantThree()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_three', 'id');
    }
}
