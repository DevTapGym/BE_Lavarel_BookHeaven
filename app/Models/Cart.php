<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'customer_id',
        'book_id',
        'quantity',
        'total_price',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cartItem()
    {
        return $this->hasMany(CartItem::class);
    }
}
