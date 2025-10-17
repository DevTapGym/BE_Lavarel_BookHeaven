<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'total_amount',
        'note',
        'shipping_fee',

        'payment_method',

        'promotion_id',
        'total_promotion_value',

        'customer_id',
        'receiver_name',
        'receiver_address',
        'receiver_phone',
    ];

    // Removed relations to paymentMethod and shippingAddress as per new requirement

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
