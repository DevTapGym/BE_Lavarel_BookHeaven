<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
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
            'book_id' => $this->book_id,
            'book_name' => $this->book->title,
            'book_author' => $this->book->author,
            'book_thumbnail' => $this->book->thumbnail,
            'unit_price' => $this->book->price,
            'sale' => $this->book->sale,
            'quantity' => $this->quantity,
            'total_price' => $this->price,
            'in_stock' => $this->book->quantity,
        ];
    }
}
