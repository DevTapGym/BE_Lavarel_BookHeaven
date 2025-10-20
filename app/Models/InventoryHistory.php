<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUserstamps;

class InventoryHistory extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'book_id',
        'code',
        'order_id',
        'import_receipt_id',
        'type',
        'qty_stock_before',
        'qty_change',
        'qty_stock_after',
        'price',
        'total_price',
        'transaction_date',
        'description',
    ];

    protected $casts = [
        'qty_stock_before' => 'integer',
        'qty_change' => 'integer',
        'qty_stock_after' => 'integer',
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function importReceipt()
    {
        return $this->belongsTo(ImportReceipt::class);
    }
}


