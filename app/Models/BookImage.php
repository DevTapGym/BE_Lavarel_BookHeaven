<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookImage extends Model
{
    protected $fillable = ['url', 'book_id'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
