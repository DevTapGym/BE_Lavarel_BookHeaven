<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'title',
        'description',
        'price',
        'thumbnail',
        'author',
        'is_active',
        'quantity',
        'sold',
        'sale_off',
    ];

    public function bookFeatures()
    {
        return $this->hasMany(BookFeature::class);
    }

    public function bookImages()
    {
        return $this->hasMany(BookImage::class);
    }

    public function bookCategories()
    {
        return $this->hasMany(BookCategory::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
