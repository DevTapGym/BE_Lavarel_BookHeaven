<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookDetailResource extends JsonResource
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
            'thumbnail' => $this->thumbnail,
            'mainText' => $this->title,
            'author' => $this->author,
            'price' => $this->price,
            'sold' => $this->sold,
            'quantity' => $this->quantity,
            'isDeleted' => $this->deleted_at ? 1 : 0,
            'deletedBy' => $this->deleted_by,
            'deletedAt' => $this->deleted_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
            'createdBy' => $this->created_by,
            'updatedBy' => $this->updated_by,
            'category' => $this->when($this->relationLoaded('categories') && $this->categories->isNotEmpty(), function () {
                $category = $this->categories->first();
                return [
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
                            'isDeleted' => $book->deleted_at ? 1 : 0,
                            'deletedBy' => $book->deleted_by,
                            'deletedAt' => $book->deleted_at,
                            'createdAt' => $book->created_at,
                            'updatedAt' => $book->updated_at,
                            'createdBy' => $book->created_by,
                            'updatedBy' => $book->updated_by,
                            'bookImages' => $book->bookImages->map(function ($image) {
                                return [
                                    'id' => $image->id,
                                    'url' => $image->url,
                                ];
                            }),
                        ];
                    }),
                ];
            }),
            'bookImages' => $this->when($this->relationLoaded('bookImages'), function () {
                return $this->bookImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->url,
                    ];
                });
            }),
        ];
    }
}
