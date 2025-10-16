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
            'totalAmount'  => $this->total_amount,
            'createdAt'    => $this->created_at ? $this->created_at->toISOString() : null,
            'updatedAt'    => $this->updated_at ? $this->updated_at->toISOString() : null,
            'createdBy'  => $this->created_by ? $this->created_by : null,
            'updatedBy'  => $this->updated_by ? $this->updated_by : null,

            'employee' => [
                'id'   => $this->employee->id,
                'name' => $this->employee->name,
            ],

            'receiptDetails' => ImportReceiptDetailResource::collection($this->importReceiptDetails),
        ];
    }
}
