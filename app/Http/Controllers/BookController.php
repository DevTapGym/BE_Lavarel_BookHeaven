<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use Throwable;


class BookController extends Controller
{

    public function indexPaginated(Request $request)
    {
        $pageSize = $request->query('size', 10);
        $paginator = Book::paginate($pageSize);
        $data = $this->paginateResponse($paginator);

        return $this->successResponse(
            200,
            'Books retrieved successfully',
            $data
        );
    }

    public function show(Book $book)
    {
        return $this->successResponse(
            200,
            'Book retrieved successfully',
            $book
        );
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title'          => 'required|string|max:255',
                'author'         => 'required|string|max:255',
                'price'          => 'required|numeric|min:0',
                'description'    => 'nullable|string',
                'thumbnail'      => 'nullable|url',
                'is_active'      => 'sometimes|boolean',
                'quantity'       => 'sometimes|integer|min:0',
                'sold'           => 'sometimes|integer|min:0',
                'sale_off'       => 'sometimes|numeric|min:0',
            ]);
            $book = Book::create($validated);
            return $this->successResponse(
                201,
                'Book created successfully',
                $book
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error creating book',
                $th->getMessage()
            );
        }
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'             => 'required|exists:books,id',
                'title'          => 'sometimes|required|string|max:255',
                'author'         => 'sometimes|required|string|max:255',
                'price'          => 'sometimes|required|numeric|min:0',
                'description'    => 'nullable|string',
                'thumbnail'      => 'nullable|url',
                'is_active'      => 'sometimes|boolean',
                'quantity'       => 'sometimes|integer|min:0',
                'sold'           => 'sometimes|integer|min:0',
                'sale_off'       => 'sometimes|numeric|min:0',
            ]);

            DB::transaction(function () use ($validated) {
                $book = Book::findOrFail($validated['id']);
                $book->update($validated);

                if (isset($validated['price']) || isset($validated['is_active'])) {
                    $cartItems = $book->cartItems()->get();

                    foreach ($cartItems as $item) {
                        $item->price = ($book->is_active) ? ($book->price * $item->quantity) : 0;
                        $item->save();

                        $cart = $item->cart;
                        $cart->total_price = $cart->cartItems()->sum('price');
                        $cart->save();
                    }
                }
            });

            return $this->successResponse(
                200,
                'Book updated successfully',
                Book::findOrFail($validated['id'])
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error updating book',
                $th->getMessage()
            );
        }
    }

    public function destroy(Book $book)
    {
        try {
            $book->delete();
            return $this->successResponse(
                200,
                'Book deleted successfully',
                null
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error deleting book',
                $th->getMessage()
            );
        }
    }

    public function attachCategories(Request $request)
    {
        $validated = $request->validate([
            'book_id'        => 'required|exists:books,id',
            'category_ids'   => 'required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $book = Book::findOrFail($validated['book_id']);
        $book->categories()->attach($validated['category_ids']); // thêm nhiều category

        return $this->successResponse(
            200,
            'Categories added to book successfully',
            $book->categories
        );
    }

    public function syncCategories(Request $request)
    {
        $validated = $request->validate([
            'book_id'        => 'required|exists:books,id',
            'category_ids'   => 'required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $book = Book::findOrFail($validated['book_id']);
        $book->categories()->sync($validated['category_ids']);

        return $this->successResponse(
            200,
            'Book categories updated successfully',
            $book->categories
        );
    }

    public function detachCategories(Request $request)
    {
        $validated = $request->validate([
            'book_id'        => 'required|exists:books,id',
            'category_ids'   => 'required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $book = Book::findOrFail($validated['book_id']);
        $book->categories()->detach($validated['category_ids']);

        return $this->successResponse(
            200,
            'Categories removed from book successfully',
            $book->categories
        );
    }

    public function getBooksByCategory($category_id)
    {
        $category = Category::findOrFail($category_id);

        return $this->successResponse(
            200,
            'Books retrieved successfully',
            $category->books
        );
    }
}
