<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    protected $fillable = [
        'recipient_name',
        'address',
        'phone_number',
        'is_default',

        'customer_id',
        'tag_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function tag()
    {
        return $this->belongsTo(AddressTag::class);
    }
}
