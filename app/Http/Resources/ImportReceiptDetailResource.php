<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportReceiptDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'quantity'     => $this->quantity,
            'total_price'  => $this->total_price,
            'supplier_name' => $this->supply->supplier->name ?? null,
            'price_supply' => $this->supply->supply_price ?? null,
            'book_id'    => $this->supply->book->id ?? null,
            'book_name'    => $this->supply->book->title ?? null,
        ];
    }
}
