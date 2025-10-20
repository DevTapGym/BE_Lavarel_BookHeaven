<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'bookId' => $this->book_id,
            'bookTitle' => $this->book->title ?? '',
            'orderId' => $this->order_id ?? 0,
            'orderNumber' => $this->order->order_number ?? '',
            'importReceiptId' => $this->import_receipt_id ?? 0,
            'importReceiptNumber' => $this->import_receipt->import_receipt_number ?? '',
            'type' => $this->type,
            'qtyStockBefore' => $this->qty_stock_before,
            'qtyChange' => $this->qty_change,
            'qtyStockAfter' => $this->qty_stock_after,
            'price' => $this->price !== null ? (float) $this->price : null,
            'totalPrice' => $this->total_price !== null ? (float) $this->total_price : null,
            'transactionDate' => $this->transaction_date,
            'description' => $this->description,
        ];
    }
}


