<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportReceiptDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'quantity'     => $this->quantity,
            'returnQty'    => $this->return_qty ?? 0,
            'price'        => $this->price ? (float) $this->price : null,
            'totalPrice'  => $this->quantity * $this->supply->supply_price,
            'supply' => [
                'id' => $this->supply->id,
                'supplyPrice' => $this->supply->supply_price,
                'quantity' => $this->supply->quantity,
                'totalPrice' => $this->supply->total_price,
                'book' => [
                    'id' => $this->supply->book->id,
                    'title' => $this->supply->book->title,
                ],
                'supplier' => [
                    'id' => $this->supply->supplier->id,
                    'name' => $this->supply->supplier->name,
                ],
            ],
        ];
    }
}
