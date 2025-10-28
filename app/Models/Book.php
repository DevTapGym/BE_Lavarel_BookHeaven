<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'title',
        'barcode',
        'description',
        'price',
        'capital_price',
        'thumbnail',
        'author',
        'is_active',
        'quantity',
        'sold',
        'sale_off',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'capital_price' => 'decimal:2',
        'quantity' => 'integer',
        'sold' => 'integer',
        'sale_off' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'book_category', 'book_id', 'category_id');
    }

    public function bookFeatures()
    {
        return $this->hasMany(BookFeature::class);
    }

    public function bookImages()
    {
        return $this->hasMany(BookImage::class);
    }


    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function supplies()
    {
        return $this->hasMany(Supply::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
