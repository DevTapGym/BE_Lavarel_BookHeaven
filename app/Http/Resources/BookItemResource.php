<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookItemResource extends JsonResource
{
    public function toArray($request)
    {
        $category = $this->categories()->first();
        return [
            'id' => $this->id,
            'thumbnail' => $this->thumbnail,
            'mainText' => $this->title,
            'barcode' => $this->barcode,
            'author' => $this->author,
            'capitalPrice' => $this->capital_price,
            'price' => $this->price,
            'sold' => $this->sold,
            'quantity' => $this->quantity,
            'isDeleted' => (int)($this->deleted_at ? 1 : 0),
            'deletedBy' => $this->deleted_by ?? null,
            'deletedAt' => optional($this->deleted_at)?->toISOString(),
            'createdAt' => optional($this->created_at)?->toISOString(),
            'updatedAt' => optional($this->updated_at)?->toISOString(),
            'createdBy' => $this->created_by ?? null,
            'updatedBy' => $this->updated_by ?? null,
            'category' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
                'books' => $category->books->map(function ($book) {
                    return [
                        'id' => $book->id,
                        'thumbnail' => $book->thumbnail,
                        'mainText' => $book->title,
                        'author' => $book->author,
                        'price' => $book->price,
                        'sold' => $book->sold,
                        'quantity' => $book->quantity,
                        'isDeleted' => (int)($book->deleted_at ? 1 : 0),
                        'deletedBy' => $book->deleted_by ?? null,
                        'deletedAt' => optional($book->deleted_at)?->toISOString(),
                        'createdAt' => optional($book->created_at)?->toISOString(),
                        'updatedAt' => optional($book->updated_at)?->toISOString(),
                        'createdBy' => $book->created_by ?? null,
                        'updatedBy' => $book->updated_by ?? null,
                        'bookImages' => $book->bookImages->map(function ($img) {
                            return [
                                'id' => $img->id,
                                'url' => $img->url,
                            ];
                        })
                    ];
                })
            ] : null,
            'bookImages' => $this->bookImages->map(function ($img) {
                return [
                    'id' => $img->id,
                    'url' => $img->url,
                ];
            })
        ];
    }
}
