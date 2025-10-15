<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderForWebResource extends JsonResource
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
            'totalPrice' => $this->total_amount,
            'receiverEmail' => $this->shippingAddress->customer->user->email ?? null,
            'receiverName' => $this->shippingAddress->recipient_name ?? null,
            'receiverAddress' => $this->shippingAddress->address ?? null,
            'receiverPhone' => $this->shippingAddress->phone_number ?? null,
            'paymentMethod' => $this->paymentMethod->name ?? null,
            'vnpTxnRef' => $this->vnp_txn_ref ?? null,
            'isDeleted' => $this->deleted_at ? 1 : 0,
            'deletedBy' => $this->deleted_by ?? null,
            'deletedAt' => $this->deleted_at,
            'createdBy' => $this->created_by ?? null,
            'updatedBy' => $this->updated_by ?? null,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
            'customer' => $this->when($this->relationLoaded('shippingAddress'), function () {
                $customer = $this->shippingAddress->customer;
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'address' => $customer->address,
                    'phone' => $customer->phone,
                    'email' => $customer->user->email ?? null,
                    'birthday' => $customer->birthday,
                    'gender' => $customer->gender,
                    'createdAt' => $customer->created_at,
                    'updatedAt' => $customer->updated_at,
                    'createdBy' => $customer->created_by ?? null,
                    'updatedBy' => $customer->updated_by ?? null,
                    'isOauthUser' => $customer->is_oauth_user ?? false,
                    'cart' => $this->when($customer->relationLoaded('cart'), function () use ($customer) {
                        $cart = $customer->cart;
                        if (!$cart) return null;
                        return [
                            'id' => $cart->id,
                            'count' => $cart->count,
                            'sumPrice' => $cart->total_price,
                            'cartItems' => $cart->cartItems->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'quantity' => $item->quantity,
                                    'price' => $item->price,
                                    'book' => [
                                        'id' => $item->book->id,
                                        'thumbnail' => $item->book->thumbnail,
                                        'mainText' => $item->book->title,
                                        'author' => $item->book->author,
                                        'price' => $item->book->price,
                                        'sold' => $item->book->sold,
                                        'quantity' => $item->book->quantity,
                                        'isDeleted' => $item->book->deleted_at ? 1 : 0,
                                        'deletedBy' => $item->book->deleted_by ?? null,
                                        'deletedAt' => $item->book->deleted_at,
                                        'createdAt' => $item->book->created_at,
                                        'updatedAt' => $item->book->updated_at,
                                        'createdBy' => $item->book->created_by ?? null,
                                        'updatedBy' => $item->book->updated_by ?? null,
                                        'category' => $item->book->categories->first() ? [
                                            'id' => $item->book->categories->first()->id,
                                            'name' => $item->book->categories->first()->name,
                                            'books' => $item->book->categories->first()->books->pluck('title')->toArray(),
                                        ] : null,
                                        'bookImages' => $item->book->bookImages->map(function ($image) {
                                            return [
                                                'id' => $image->id,
                                                'url' => $image->url,
                                            ];
                                        }),
                                    ],
                                ];
                            }),
                        ];
                    }),
                ];
            }),
            'orderItems' => $this->when($this->relationLoaded('orderItems'), function () {
                return $this->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'book' => [
                            'id' => $item->book->id,
                            'thumbnail' => $item->book->thumbnail,
                            'mainText' => $item->book->title,
                            'author' => $item->book->author,
                            'price' => $item->book->price,
                            'sold' => $item->book->sold,
                            'quantity' => $item->book->quantity,
                            'isDeleted' => $item->book->deleted_at ? 1 : 0,
                            'deletedBy' => $item->book->deleted_by ?? null,
                            'deletedAt' => $item->book->deleted_at,
                            'createdAt' => $item->book->created_at,
                            'updatedAt' => $item->book->updated_at,
                            'createdBy' => $item->book->created_by ?? null,
                            'updatedBy' => $item->book->updated_by ?? null,
                            'category' => $item->book->categories->first() ? [
                                'id' => $item->book->categories->first()->id,
                                'name' => $item->book->categories->first()->name,
                                'books' => $item->book->categories->first()->books->pluck('title')->toArray(),
                            ] : null,
                            'bookImages' => $item->book->bookImages->map(function ($image) {
                                return [
                                    'id' => $image->id,
                                    'url' => $image->url,
                                ];
                            }),
                        ],
                    ];
                });
            }),
            'orderShippingEvents' => $this->when($this->relationLoaded('statusHistories'), function () {
                return $this->statusHistories->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'createdBy' => $history->created_by ?? null,
                        'createdAt' => $history->created_at,
                        'note' => $history->note,
                        'shippingStatus' => [
                            'id' => $history->orderStatus->id,
                            'status' => $history->orderStatus->name,
                        ],
                    ];
                });
            }),
        ];
    }
}
