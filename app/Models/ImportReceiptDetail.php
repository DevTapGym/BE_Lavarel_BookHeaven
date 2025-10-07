<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportReceiptDetail extends Model
{
    protected $fillable = [
        'quantity',
        'total_price',

        'import_receipt_id',
        'supply_id',
    ];

    public function importReceipt()
    {
        return $this->belongsTo(ImportReceipt::class);
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }
}
