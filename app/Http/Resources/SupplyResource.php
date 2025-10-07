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
            'book'        => $this->whenLoaded('book'),     // eager load book
            'supplier'    => $this->whenLoaded('supplier'), // eager load supplier
            'supplyPrice' => (float) $this->supply_price,
        ];
    }
}
