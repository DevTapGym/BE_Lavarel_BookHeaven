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

        'shipping_address_id',
        'payment_method_id',
    ];

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(ShippingAddress::class);
    }

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
}
