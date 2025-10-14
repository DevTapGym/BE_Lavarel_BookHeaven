<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\ImportReceipt;
use App\Models\ImportReceiptDetail;
use App\Models\Supply;
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
        $book->load('categories', 'bookImages', 'bookfeatures');

        return $this->successResponse(
            200,
            'Book retrieved successfully',
            $book
        );
    }

    public function search($search)
    {
        $books = Book::where('title', 'like', "%$search%")
            ->orWhere('author', 'like', "%$search%")
            ->orWhere('description', 'like', "%$search%")
            ->where('is_active', true)
            ->get();

        return $this->successResponse(
            200,
            'Search results retrieved successfully',
            $books
        );
    }

    public function getRandomBooks()
    {
        $books = Book::where('is_active', true)
            ->inRandomOrder()
            ->take(3)
            ->get();

        return $this->successResponse(
            200,
            'Random books retrieved successfully',
            $books
        );
    }

    public function getBookSaleOff()
    {
        $books = Book::where('is_active', true)
            ->where('sale_off', '>', 0)
            ->orderBy('sale_off', 'desc')
            ->take(5)
            ->get();

        return $this->successResponse(
            200,
            'Books on sale retrieved successfully',
            $books
        );
    }

    public function getPopularBooks()
    {
        $books = Book::where('is_active', true)
            ->where('sale_off', '=', 0)
            ->orderBy('sold', 'desc')
            ->take(5)
            ->get();

        return $this->successResponse(
            200,
            'Popular books retrieved successfully',
            $books
        );
    }

    public function getTop3BestSellingBooksByYear(Request $request)
    {
        try {
            $year = (int) $request->input('year', now()->year);

            // Validate year range
            if ($year < 2020 || $year > now()->year + 1) {
                return $this->errorResponse(
                    400,
                    'Bad Request',
                    'Year must be between 2020 and ' . (now()->year + 1)
                );
            }

            // Get top 3 best selling books by year based on order items
            $topBooks = Book::select([
                'books.id',
                'books.title',
                'books.author',
                'books.price',
                'books.thumbnail',
                'books.sale_off',
                DB::raw('CAST(SUM(order_items.quantity) AS UNSIGNED) as sold')
            ])
                ->join('order_items', 'books.id', '=', 'order_items.book_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereYear('orders.created_at', $year)
                ->where('books.is_active', true)
                ->groupBy([
                    'books.id',
                    'books.title',
                    'books.author',
                    'books.price',
                    'books.thumbnail',
                    'books.sale_off'
                ])
                ->orderBy('sold', 'desc')
                ->limit(3)
                ->get();

            return $this->successResponse(
                200,
                "Top 3 best selling books in {$year} retrieved successfully",
                $topBooks
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error retrieving top selling books',
                $th->getMessage()
            );
        }
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

    public function getBookBanner()
    {
        try {
            $bannerBooks = [];

            $newestBook = Book::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($newestBook) {
                $bannerBooks[] = $newestBook;
            }

            $bestSellerBook = Book::where('is_active', true)
                ->where('sold', '>', 0)
                ->orderBy('sold', 'desc')
                ->first();

            if ($bestSellerBook) {
                $bannerBooks[] = $bestSellerBook;
            }

            // Lấy sách từ phiếu nhập mới nhất
            $latestImportReceipt = ImportReceipt::orderBy('created_at', 'desc')->first();

            $newStockBook = null;
            if ($latestImportReceipt) {
                // Lấy 1 sách ngẫu nhiên từ chi tiết phiếu nhập mới nhất
                $newStockBook = Book::whereHas('supplies', function ($query) use ($latestImportReceipt) {
                    $query->whereHas('importReceiptDetails', function ($subQuery) use ($latestImportReceipt) {
                        $subQuery->where('import_receipt_id', $latestImportReceipt->id);
                    });
                })
                    ->where('is_active', true)
                    ->inRandomOrder()
                    ->first();
            }

            if ($newStockBook) {
                $bannerBooks[] = $newStockBook;
            }

            $highestDiscountBook = Book::where('is_active', true)
                ->where('sale_off', '>', 0)
                ->orderBy('sale_off', 'desc')
                ->first();

            if ($highestDiscountBook) {
                $bannerBooks[] =  $highestDiscountBook;
            }

            return $this->successResponse(
                200,
                'Banner books retrieved successfully',
                $bannerBooks
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error retrieving banner books',
                $th->getMessage()
            );
        }
    }
}
