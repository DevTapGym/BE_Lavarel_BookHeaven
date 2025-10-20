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
            'type'          => $this->type ?? 'IMPORT',
            'status'        => $this->status ?? 'processing',
            'notes'         => $this->notes,
            'totalAmount'  => $this->total_amount,
            'returnFee'    => $this->return_fee ? (float) $this->return_fee : null,
            'returnFeeType' => $this->return_fee_type,
            'totalRefundAmount' => $this->total_refund_amount ? (float) $this->total_refund_amount : null,
            'parentId'     => $this->parent_id,
            'createdAt'    => $this->created_at ? $this->created_at->toISOString() : null,
            'updatedAt'    => $this->updated_at ? $this->updated_at->toISOString() : null,
            'createdBy'  => $this->created_by ? $this->created_by : null,
            'updatedBy'  => $this->updated_by ? $this->updated_by : null,

            'employee' => [
                'id'   => $this->employee->id,
                'name' => $this->employee->name,
            ],

            'importReceiptDetails' => ImportReceiptDetailResource::collection($this->importReceiptDetails),
            
            'returnReceipts' => $this->whenLoaded('returnReceipts', function () {
                return $this->returnReceipts->sortByDesc('created_at')->values()->map(function ($returnReceipt) {
                    return [
                        'id' => $returnReceipt->id,
                        'receipt_number' => $returnReceipt->receipt_number,
                        'type' => $returnReceipt->type,
                        'totalAmount' => (float) $returnReceipt->total_amount,
                        'returnFee' => $returnReceipt->return_fee ? (float) $returnReceipt->return_fee : null,
                        'returnFeeType' => $returnReceipt->return_fee_type,
                        'totalRefundAmount' => $returnReceipt->total_refund_amount ? (float) $returnReceipt->total_refund_amount : null,
                        'createdAt' => $returnReceipt->created_at ? $returnReceipt->created_at->toISOString() : null,
                        'importReceiptDetails' => $returnReceipt->importReceiptDetails->map(function ($detail) {
                            return [
                                'id' => $detail->id,
                                'quantity' => $detail->quantity,
                                'price' => (float) $detail->price,
                                'returnQty' => $detail->return_qty ?? 0,
                            ];
                        }),
                    ];
                });
            }),
        ];
    }
}
