<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'unit_price' => $this->price,
            'total_price' => $this->price * $this->quantity,
            'book_id' => $this->book->id,
            'book_title' => $this->book->title,
            'book_author' => $this->book->author,
            'book_thumbnail' => $this->book->thumbnail,
            'book_description' => $this->book->description,
            'book_current_price' => $this->book->price,
            'book_sale_off' => $this->book->sale_off,
        ];
    }
}
