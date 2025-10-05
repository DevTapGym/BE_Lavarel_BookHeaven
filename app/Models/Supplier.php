<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'address',
        'name',
        'email',
        'phone',
    ];

    public function supplies()
    {
        return $this->hasMany(Supply::class);
    }

    public function books()
    {
        return $this->hasManyThrough(Book::class, Supply::class, 'supplier_id', 'id', 'id', 'book_id');
    }
}
