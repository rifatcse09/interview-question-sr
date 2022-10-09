<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'file_path', 'thumbnail'
    ];

    public function getFilePathAttribute($value)
    {
        return  $value ? 'storage/' . $value : null;
    }
}
