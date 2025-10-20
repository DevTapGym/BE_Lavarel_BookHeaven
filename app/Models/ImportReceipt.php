<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUserstamps;

class ImportReceipt extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'receipt_number',
        'type',
        'total_amount',
        'notes',
        'employee_id',
        'parent_id',
        'return_fee',
        'return_fee_type',
        'total_refund_amount',
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

    public function parentReceipt()
    {
        return $this->belongsTo(ImportReceipt::class, 'parent_id');
    }

    public function returnReceipts()
    {
        return $this->hasMany(ImportReceipt::class, 'parent_id');
    }
}
