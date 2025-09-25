<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function carts()
    {
        return $this->hasOne(Cart::class);
    }
}
