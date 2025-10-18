<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'type',
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
        'receiver_email',

        'return_fee',
        'return_fee_type',
        'total_refund_amount',
        'vnp_txn_ref',
        'payment_status',
        'status',
        'parent_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'total_promotion_value' => 'decimal:2',
        'return_fee' => 'decimal:2',
        'total_refund_amount' => 'decimal:2',
        'customer_id' => 'integer',
        'promotion_id' => 'integer',
        'parent_id' => 'integer',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->setTimezone('Asia/Ho_Chi_Minh');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->setTimezone('Asia/Ho_Chi_Minh');
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function inventoryHistories()
    {
        return $this->hasMany(InventoryHistory::class);
    }

    public function returnOrders()
    {
        return $this->hasMany(Order::class, 'parent_id');
    }

    public function parentOrder()
    {
        return $this->belongsTo(Order::class, 'parent_id');
    }
}
