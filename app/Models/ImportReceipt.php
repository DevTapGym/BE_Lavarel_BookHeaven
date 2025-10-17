<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUserstamps;

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
}
