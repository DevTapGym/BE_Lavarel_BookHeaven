<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportReceipt extends Model
{
    protected $fillable = [
        'receipt_number',
        'total_amount',
        'notes',

        'employee_id',
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
