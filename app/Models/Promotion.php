<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUserstamps;

class Promotion extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'code',
        'name',
        'status',
        'promotion_type',
        'promotion_value',
        'is_max_promotion_value',
        'max_promotion_value',
        'order_min_value',
        'start_date',
        'end_date',
        'qty_limit',
        'is_once_per_customer',
        'note',
        'is_deleted',
        'deleted_by',
        'deleted_at',
    ];

    protected $casts = [
        "status" => 'boolean',
        'promotion_value' => 'decimal:2',
        'max_promotion_value' => 'decimal:2',
        'order_min_value' => 'decimal:2',
        'is_max_promotion_value' => 'boolean',
        'is_once_per_customer' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'deleted_at' => 'datetime',
        'qty_limit' => 'integer',
        'is_deleted' => 'integer',
        'status' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
