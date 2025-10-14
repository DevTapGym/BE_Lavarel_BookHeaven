<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'total_amount' => $this->total_amount,
            'shipping_fee' => $this->shipping_fee,
            'note' => $this->note,
            'created_at' => $this->created_at,

            // Shipping Address Information
            'shipping_address' => [
                'id' => $this->shippingAddress->id ?? null,
                'recipient_name' => $this->shippingAddress->recipient_name ?? null,
                'address' => $this->shippingAddress->address ?? null,
                'phone_number' => $this->shippingAddress->phone_number ?? null,
                'tag_name' => $this->shippingAddress->tag->name ?? null,
            ],

            // Payment Method
            'payment_method' => [
                'id' => $this->paymentMethod->id ?? null,
                'name' => $this->paymentMethod->name ?? null,
            ],

            // Order Items
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),

            // Order Status History
            'status_histories' => $this->whenLoaded('statusHistories', function () {
                return $this->statusHistories->map(function ($statusHistory) {
                    return [
                        'id' => $statusHistory->id,
                        'status_name' => $statusHistory->orderStatus->name ?? null,
                        'description' => $statusHistory->orderStatus->description ?? null,
                        'status_sequence' => $statusHistory->orderStatus->sequence ?? null,
                        'note' => $statusHistory->note,
                        'created_at' => $statusHistory->created_at,
                    ];
                });
            }),

            // Current Status
            'current_status' => $this->whenLoaded('statusHistories', function () {
                $latestStatus = $this->statusHistories->sortByDesc('created_at')->first();
                return [
                    'name' => $latestStatus->orderStatus->name ?? null,
                    'sequence' => $latestStatus->orderStatus->sequence ?? null,
                    'updated_at' => $latestStatus->created_at ?? null,
                ];
            }),

            // Summary
            'summary' => [
                'total_items' => $this->whenLoaded('orderItems', $this->orderItems->count()),
                'total_quantity' => $this->whenLoaded('orderItems', $this->orderItems->sum('quantity')),
                'subtotal' => $this->total_amount,
                'shipping_fee' => $this->shipping_fee ?? 0,
                'final_total' => $this->total_amount + ($this->shipping_fee ?? 0),
            ],
        ];
    }
}
