<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUserstamps;
use Illuminate\Database\Eloquent\Builder;

class ImportReceipt extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'receipt_number',
        'total_amount',
        'notes',
        'employee_id',
        'created_by',
        'updated_by',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function importReceiptDetails()
    {
        return $this->hasMany(ImportReceiptDetail::class);
    }

    // Query Scopes cho Spatie Query Builder

    /**
     * Scope để filter theo ngày tạo từ (from date)
     */
    public function scopeCreatedFrom(Builder $query, $date): Builder
    {
        return $query->whereDate('created_at', '>=', $date);
    }

    /**
     * Scope để filter theo ngày tạo đến (to date)
     */
    public function scopeCreatedTo(Builder $query, $date): Builder
    {
        return $query->whereDate('created_at', '<=', $date);
    }

    /**
     * Scope để filter theo ngày cập nhật từ (from date)
     */
    public function scopeUpdatedFrom(Builder $query, $date): Builder
    {
        return $query->whereDate('updated_at', '>=', $date);
    }

    /**
     * Scope để filter theo ngày cập nhật đến (to date)
     */
    public function scopeUpdatedTo(Builder $query, $date): Builder
    {
        return $query->whereDate('updated_at', '<=', $date);
    }

    /**
     * Scope để filter theo giá từ (minimum price)
     */
    public function scopePriceFrom(Builder $query, $price): Builder
    {
        return $query->where('total_amount', '>=', $price);
    }

    /**
     * Scope để filter theo giá đến (maximum price)
     */
    public function scopePriceTo(Builder $query, $price): Builder
    {
        return $query->where('total_amount', '<=', $price);
    }
}
