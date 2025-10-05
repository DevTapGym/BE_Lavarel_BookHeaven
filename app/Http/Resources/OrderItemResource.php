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
            'book' => [
                'id' => $this->book->id,
                'title' => $this->book->title,
                'author' => $this->book->author,
                'thumbnail' => $this->book->thumbnail,
                'description' => $this->book->description,
                'current_price' => $this->book->price,
                'sale_off' => $this->book->sale_off,
            ],
        ];
    }
}
