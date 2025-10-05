<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportReceiptResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'receipt_number' => $this->receipt_number,
            'notes'         => $this->notes,
            'total_amount'  => $this->total_amount,
            'created_at'    => $this->created_at->format('Y-m-d H:i:s'),

            'employee' => [
                'id'   => $this->employee->id,
                'name' => $this->employee->name,
            ],

            'details' => ImportReceiptDetailResource::collection($this->importReceiptDetails),
        ];
    }
}
