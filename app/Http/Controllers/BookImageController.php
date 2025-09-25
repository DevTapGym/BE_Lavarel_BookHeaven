<?php

namespace App\Http\Controllers;

use App\Models\BookImage;
use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookImageController extends Controller
{
    public function getBookImages($book_id)
    {
        $book = Book::with('bookImages')->findOrFail($book_id);

        return $this->successResponse(
            200,
            'Book images retrieved successfully',
            $book->bookImages
        );
    }

    public function addBookImages(Request $request)
    {
        $validated = $request->validate([
            'book_id'  => 'required|exists:books,id',
            'images'   => 'required|array',
            'images.*' => 'required|url',
        ]);

        try {
            $book = Book::findOrFail($validated['book_id']);

            $createdImages = [];
            DB::transaction(function () use ($book, $validated, &$createdImages) {
                foreach ($validated['images'] as $url) {
                    $createdImages[] = $book->bookImages()->create(['url' => $url]);
                }
            });

            return $this->successResponse(
                201,
                'Images added successfully',
                $createdImages
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error adding images',
                $th->getMessage()
            );
        }
    }

    public function deleteBookImage($image_id)
    {
        try {
            $image = BookImage::findOrFail($image_id);
            $image->delete();

            return $this->successResponse(
                200,
                'Image deleted successfully',
                null
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error deleting image',
                $th->getMessage()
            );
        }
    }

    public function deleteAllBookImages($book_id)
    {
        try {
            $book = Book::findOrFail($book_id);
            $book->bookImages()->delete();

            return $this->successResponse(
                200,
                'All images deleted successfully',
                null
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error deleting all images',
                $th->getMessage()
            );
        }
    }
}
