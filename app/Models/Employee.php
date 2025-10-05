<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'position',
        'date_of_birth',
        'salary',
        'hire_date',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
    ];

    public function importReceipts()
    {
        return $this->hasMany(ImportReceipt::class);
    }
}
