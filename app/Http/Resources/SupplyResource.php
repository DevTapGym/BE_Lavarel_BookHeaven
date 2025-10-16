<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'book'        => [
                'id'       => $this->book->id,
                'mainText' => $this->book->title,
            ],
            'supplier'    => [
                'id'   => $this->supplier->id,
                'name' => $this->supplier->name,
            ],
            'supplyPrice' => (float) $this->supply_price,
            'createdAt'  => optional($this->created_at)?->toISOString(),
            'updatedAt'  => optional($this->updated_at)?->toISOString(),
        ];
    }
}
