<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookFeature extends Model
{
    protected $fillable = ['feature_name', 'book_id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
