<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookFeature extends Model
{
    protected $fillable = ['feature_name', 'book_id'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
