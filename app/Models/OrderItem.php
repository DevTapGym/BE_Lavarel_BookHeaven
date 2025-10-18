<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'quantity',
        'price',
        'capital_price',
        'total_price',
        'total_capital_price',
        'return_qty',

        'order_id',
        'book_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'capital_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'total_capital_price' => 'decimal:2',
        'return_qty' => 'integer',
        'order_id' => 'integer',
        'book_id' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
