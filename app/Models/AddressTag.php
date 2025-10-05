<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddressTag extends Model
{
    protected $fillable = ['name'];

    protected $hidden = ['created_at', 'updated_at'];

    public function addresses()
    {
        return $this->hasMany(ShippingAddress::class);
    }
}
