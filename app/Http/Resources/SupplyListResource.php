<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplyPrice' => $this->supply_price,
            'quantity' => $this->quantity,
            'updatedAt' => $this->updated_at ? $this->updated_at->toISOString() : null,
            'createdAt' => $this->created_at ? $this->created_at->toISOString() : null,

            'book' => [
                'id' => $this->book?->id,
                'mainText' => $this->book?->title,
                'author' => $this->book?->author,
                'price' => $this->book?->price,
                'thumbnail' => $this->book?->thumbnail,
            ],

            'supplier' => [
                'id' => $this->supplier?->id,
                'name' => $this->supplier?->name,
                'address' => $this->supplier?->address,
                'phone' => $this->supplier?->phone,
                'email' => $this->supplier?->email,
            ],
        ];
    }
}
