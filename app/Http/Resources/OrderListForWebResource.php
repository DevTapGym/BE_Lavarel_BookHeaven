<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListForWebResource extends JsonResource
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
            'createdAt' => $this->created_at,
            'totalPrice' => $this->total_amount,
            'receiverAddress' => $this->receiver_address,
            'receiverPhone' => $this->receiver_phone,
            'receiverName' => $this->receiver_name,
            'orderShippingEvents' => $this->statusHistories->map(function ($history) {
                return [
                    'id' => $history->id,
                    'createdAt' => $history->created_at,
                    'shippingStatus' => [
                        'id' => $history->orderStatus->id,
                        'status' => $history->orderStatus->name,
                    ],
                    'note' => $history->note,
                ];
            }),
            'orderDetails' => $this->orderItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'bookId' => $item->book_id,
                    'bookTitle' => $item->book->title,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'thumbnail' => $item->book->bookImages->where('is_thumbnail', true)->first()?->image_url ?? null,
                ];
            }),
        ];
    }
}
