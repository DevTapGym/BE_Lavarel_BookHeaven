<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this;
        $customer = $user->customer;
        $cart = $customer?->cart;

        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'phone' => $customer?->phone,
            'role' => $user->roles()->pluck('name')->first(),
            'employee' => $user->employee ? [
                'id' => $user->employee->id,
                'name' => $user->employee->name,
                'address' => $user->employee->address,
                'phone' => $user->employee->phone,
                'email' => $user->employee->email,
                'dateOfBirth' => $user->employee->date_of_birth
            ] : null,
            'customer' => $customer ? [
                'id' => $customer->id,
                'name' => $customer->name,
                'address' => $customer->address,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'birthday' => $customer->date_of_birth,
                'gender' => $customer->gender,
                'createdAt' => optional($customer->created_at)?->toISOString(),
                'updatedAt' => optional($customer->updated_at)?->toISOString(),
                'createdBy' => $customer->created_by ?? null,
                'updatedBy' => $customer->updated_by ?? null,
                'isOauthUser' => false,
                'cart' => $cart ? [
                    'id' => $cart->id,
                    'count' => $cart->count,
                    'sumPrice' => $cart->total_price,
                    'cartItems' => $cart->cartItems->map(function ($item) {
                        $book = $item->book;
                        $category = $book?->categories()->first();
                        return [
                            'id' => $item->id,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'book' => $book ? [
                                'id' => $book->id,
                                'thumbnail' => $book->thumbnail,
                                'mainText' => $book->title,
                                'author' => $book->author,
                                'price' => $book->price,
                                'sold' => $book->sold,
                                'quantity' => $book->quantity,
                                'isDeleted' => (int) ($book->deleted_at ? 1 : 0),
                                'deletedBy' => $book->deleted_by ?? null,
                                'deletedAt' => optional($book->deleted_at)?->toISOString(),
                                'createdAt' => optional($book->created_at)?->toISOString(),
                                'updatedAt' => optional($book->updated_at)?->toISOString(),
                                'createdBy' => $book->created_by ?? null,
                                'updatedBy' => $book->updated_by ?? null,
                                'category' => $category ? [
                                    'id' => $category->id,
                                    'name' => $category->name,
                                    'books' => $category->books()->pluck('title')->take(10),
                                ] : null,
                                'bookImages' => $book->bookImages->map(function ($img) {
                                    return [
                                        'id' => $img->id,
                                        'url' => $img->url,
                                    ];
                                }),
                            ] : null,
                        ];
                    }),
                ] : null,
            ] : null,
        ];
    }
}
