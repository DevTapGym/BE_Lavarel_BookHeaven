<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookImage;
use App\Models\ImportReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Http\Resources\BookListResource;
use App\Http\Resources\BookDetailResource;
use App\Http\Resources\BookItemResource;
use Throwable;
use Exception;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

    public function indexPaginatedForWeb(Request $request)
    {
        try {
            $pageSize = $request->input('size', 10);
            $page = $request->input('page', 1);

            $books = QueryBuilder::for(Book::class)
                ->allowedFilters([
                    // Lọc theo tên sách (title)
                    AllowedFilter::partial('title'),
                    AllowedFilter::partial('mainText', 'title'),
                    AllowedFilter::partial('author'),
                    AllowedFilter::partial('barcode'),

                    // Lọc theo tên thể loại
                    AllowedFilter::callback('category', function ($query, $value) {
                        $categories = is_array($value)
                            ? $value
                            : explode(',', $value); // Tách chuỗi thành mảng nếu truyền bằng dấu phẩy

                        $query->whereHas('categories', function ($q) use ($categories) {
                            $q->where(function ($sub) use ($categories) {
                                foreach ($categories as $cat) {
                                    $sub->orWhere('categories.name', 'like', '%' . trim($cat) . '%');
                                }
                            });
                        });
                    }),


                    // Lọc theo khoảng giá
                    AllowedFilter::callback('price_min', function ($query, $value) {
                        $query->where('price', '>=', $value);
                    }),
                    AllowedFilter::callback('price_max', function ($query, $value) {
                        $query->where('price', '<=', $value);
                    }),
                ])
                ->allowedSorts([
                    'sold',
                    'created_at',
                    'quantity',
                    'updated_at',
                    'price',
                    'title',
                    'author',
                    'barcode',
                ])
                ->defaultSort('-sold') // Mặc định sort
                ->with(['categories', 'bookImages'])
                ->paginate($pageSize, ['*'], 'page', $page);

            return response()->json([
                'status' => 0,
                'message' => 'Lấy danh sách sách thành công',
                'data' => new BookListResource($books)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 1,
                'message' => 'Lỗi khi lấy danh sách sách: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getAllBooks()
    {
        $books = Book::where('is_active', true)->get();
        return response()->json([
            'status' => 200,
            'message' => 'Lấy danh sách sách thành công',
            'data' => BookItemResource::collection($books)
        ]);
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

    public function showForWeb(Book $book)
    {
        try {
            // Load relationships: categories với books và bookImages của từng book
            $book->load([
                'categories.books.bookImages',
                'bookImages'
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Lấy thông tin sách thành công',
                'data' => new BookDetailResource($book)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Lỗi khi lấy thông tin sách: ' . $e->getMessage(),
            ], 500);
        }
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
                'barcode'        => 'nullable|string|max:100|unique:books,barcode',
                'author'         => 'required|string|max:255',
                'price'          => 'required|numeric|min:0',
                'description'    => 'nullable|string',
                'thumbnail'      => 'nullable|string',
                'is_active'      => 'sometimes|boolean',
                'quantity'       => 'sometimes|integer|min:0',
                'sold'           => 'sometimes|integer|min:0',
                'category_id'    => 'sometimes|integer|exists:categories,id',
                'sale_off'       => 'sometimes|numeric|min:0',
                'bookImages'     => 'sometimes|array',
                'bookImages.*.url'   => 'string',
            ]);

            return DB::transaction(function () use ($validated) {
                // Tạo book (loại bỏ category_id và bookImages khỏi dữ liệu tạo book)
                $bookData = collect($validated)->except(['category_id', 'bookImages'])->toArray();
                $book = Book::create($bookData);

                // Attach category nếu có
                if (isset($validated['category_id'])) {
                    $book->categories()->attach($validated['category_id']);
                }

                // Tạo book images nếu có
                if (isset($validated['bookImages']) && is_array($validated['bookImages'])) {
                    foreach ($validated['bookImages'] as $imageUrl) {
                        $book->bookImages()->create([
                            'url' => $imageUrl['url'],
                        ]);
                    }
                }

                // Load categories và bookImages để trả về
                $book->load(['categories', 'bookImages']);

                return $this->successResponse(
                    201,
                    'Book created successfully',
                    $book
                );
            });
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
                'title'          => 'sometimes|string|max:255',
                'barcode'        => 'nullable|sometimes|string|max:100|unique:books,barcode,' . $request->input('id'),
                'author'         => 'sometimes|string|max:255',
                'price'          => 'sometimes|numeric|min:0',
                'description'    => 'nullable|string',
                'thumbnail'      => 'nullable|string',
                'is_active'      => 'sometimes|boolean',
                'quantity'       => 'sometimes|integer|min:0',
                'sold'           => 'sometimes|integer|min:0',
                'sale_off'       => 'sometimes|numeric|min:0',
                'category_id'    => 'sometimes|integer|exists:categories,id',
                'bookImages'     => 'sometimes|array',
                'bookImages.*.url' => 'required|string',
            ]);

            DB::transaction(function () use ($validated) {
                $book = Book::findOrFail($validated['id']);

                // Cập nhật thông tin cơ bản
                $book->update(collect($validated)->except(['category_id', 'bookImages'])->toArray());

                // Cập nhật category nếu có
                if (isset($validated['category_id'])) {
                    $book->categories()->sync([$validated['category_id']]);
                }

                // Cập nhật ảnh nếu có
                if (isset($validated['bookImages'])) {
                    $book->bookImages()->delete();
                    foreach ($validated['bookImages'] as $imageData) {
                        $book->bookImages()->create(['url' => $imageData['url']]);
                    }
                }

                // Nếu thay đổi giá hoặc trạng thái => cập nhật lại giỏ hàng
                if (isset($validated['price']) || isset($validated['is_active'])) {
                    $cartItems = $book->cartItems()->get();

                    foreach ($cartItems as $item) {
                        $item->price = $book->is_active ? ($book->price * $item->quantity) : 0;
                        $item->save();

                        $cart = $item->cart;
                        $cart->total_price = $cart->cartItems()->sum('price');
                        $cart->save();
                    }
                }
            });

            $updatedBook = Book::with(['categories', 'bookImages'])->findOrFail($validated['id']);

            return $this->successResponse(
                200,
                'Book updated successfully',
                $updatedBook
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
            $book->categories()->detach();
            $book->bookImages()->delete();
            $book->delete();

            return $this->successResponse(200, 'Book deleted successfully', null);
        } catch (Throwable $th) {
            return $this->errorResponse(500, 'Error deleting book', $th->getMessage());
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

    /**
     * Lấy tất cả sách đã bán trong khoảng thời gian
     * GET /api/v1/sold-books?startDate=YYYY-MM-DD&endDate=YYYY-MM-DD HH:mm:ss
     */
    public function getSoldBooksByDateRange(Request $request)
    {
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        if (!$startDate || !$endDate) {
            return $this->errorResponse(400, 'Bad Request', 'Missing startDate or endDate');
        }

        // Lấy danh sách sách và số lượng đã bán trong khoảng thời gian
        $orderItems = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('books', 'order_items.book_id', '=', 'books.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select(
                'books.id as bookID',
                'books.title as bookName',
                DB::raw('SUM(order_items.quantity) as totalQuantity')
            )
            ->groupBy('books.id', 'books.title')
            ->orderByDesc('totalQuantity')
            ->get();

        // Trả về dữ liệu gọn gàng đúng format frontend cần
        return $this->successResponse(
            200,
            'Sold books retrieved successfully',
            $orderItems
        );
    }

    /**
     * Export books to Excel file
     * GET /api/books/export-excel
     */
    public function exportToExcel()
    {
        try {
            // Lấy danh sách books với quan hệ categories và bookImages
            $books = Book::with(['categories', 'bookImages'])
                ->where('is_active', true)
                ->get();

            // Tạo spreadsheet mới
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $headers = [
                'Tên sách',
                'Mã vạch',
                'Giá bán',
                'Số lượng tồn',
                'Tác giả',
                'Thể loại',
                'Ảnh chính',
                'Ảnh phụ'
            ];

            $columnIndex = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($columnIndex . '1', $header);
                $sheet->getStyle($columnIndex . '1')->getFont()->setBold(true);
                $columnIndex++;
            }

            // Fill data
            $row = 2;
            foreach ($books as $book) {
                // Lấy tên categories (nhiều-nhiều)
                $categoryNames = $book->categories->pluck('name')->join(', ');

                // Lấy URLs của bookImages và ghép bằng dấu chấm phẩy
                $imageUrls = $book->bookImages->pluck('url')->filter()->join(';');

                $sheet->setCellValue('A' . $row, $book->title);
                $sheet->setCellValue('B' . $row, $book->barcode);
                $sheet->setCellValue('C' . $row, $book->price);
                $sheet->setCellValue('D' . $row, $book->quantity);
                $sheet->setCellValue('E' . $row, $book->author);
                $sheet->setCellValue('F' . $row, $categoryNames);
                $sheet->setCellValue('G' . $row, $book->thumbnail);
                $sheet->setCellValue('H' . $row, $imageUrls);

                $row++;
            }

            // Auto size columns
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Tạo filename với timestamp
            $fileName = 'books_' . date('Y-m-d_H-i-s') . '.xlsx';

            // Tạo writer và lưu vào temporary buffer
            $writer = new Xlsx($spreadsheet);

            // Lưu vào temporary file
            $temp_file = tempnam(sys_get_temp_dir(), 'excel_');
            $writer->save($temp_file);

            // Đọc nội dung file
            $fileContent = file_get_contents($temp_file);

            // Xóa temporary file
            unlink($temp_file);

            // Trả về response (CORS headers sẽ được thêm bởi CorsMiddleware)
            return response($fileContent, 200)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
                ->header('Cache-Control', 'max-age=0');
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Lỗi khi export file Excel',
                $th->getMessage()
            );
        }
    }

    /**
     * Import books from Excel file
     * POST /api/books/import-excel
     */
    public function importBooksFromExcel(Request $request)
    {
        try {
            // Validate file
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
            ]);

            $file = $request->file('file');

            if (!$file || !$file->isValid()) {
                return $this->errorResponse(
                    400,
                    'Bad Request',
                    'File không hợp lệ hoặc không được để trống'
                );
            }

            // Đọc file Excel
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            // Bỏ qua header row (row 1)
            $dataRows = array_slice($data, 1);

            $importedBooks = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($dataRows as $index => $row) {
                $rowNumber = $index + 2; // +2 vì bỏ header và index bắt đầu từ 0

                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    $mainText = trim($row[0] ?? '');
                    $barcode = trim($row[1] ?? '');
                    $price = $row[2] ?? null;
                    $quantity = $row[3] ?? null;
                    $author = trim($row[4] ?? '');
                    $categoryName = trim($row[5] ?? '');
                    $thumbnail = trim($row[6] ?? '');
                    $imageUrls = trim($row[7] ?? '');

                    // Validate dữ liệu
                    if (empty($mainText)) {
                        $errors[] = "Dòng {$rowNumber}: Tên sách không được để trống";
                        continue;
                    }

                    // Kiểm tra barcode trùng lặp nếu có
                    if (!empty($barcode)) {
                        $existingBook = Book::where('barcode', $barcode)->first();
                        if ($existingBook) {
                            $errors[] = "Dòng {$rowNumber}: Mã vạch '{$barcode}' đã tồn tại";
                            continue;
                        }
                    }

                    if (!is_numeric($price) || $price <= 0) {
                        $errors[] = "Dòng {$rowNumber}: Giá sách phải là số lớn hơn 0";
                        continue;
                    }

                    if (!is_numeric($quantity) || $quantity < 0) {
                        $errors[] = "Dòng {$rowNumber}: Số lượng sách phải là số >= 0";
                        continue;
                    }

                    if (empty($author)) {
                        $errors[] = "Dòng {$rowNumber}: Tác giả không được để trống";
                        continue;
                    }

                    if (empty($categoryName)) {
                        $errors[] = "Dòng {$rowNumber}: Thể loại không được để trống";
                        continue;
                    }

                    // Tìm hoặc tạo categories (có thể có nhiều category cách nhau bằng dấu phẩy)
                    $categoryNames = array_map('trim', explode(',', $categoryName));
                    $categoryIds = [];

                    foreach ($categoryNames as $catName) {
                        $category = Category::firstOrCreate(
                            ['name' => $catName],
                            ['description' => 'Auto-created from Excel import']
                        );
                        $categoryIds[] = $category->id;
                    }

                    if (empty($categoryIds)) {
                        $errors[] = "Dòng {$rowNumber}: Không thể tạo hoặc tìm thấy thể loại";
                        continue;
                    }

                    // Tạo Book
                    $book = Book::create([
                        'title' => $mainText,
                        'barcode' => !empty($barcode) ? $barcode : null,
                        'price' => $price,
                        'quantity' => $quantity,
                        'author' => $author,
                        'thumbnail' => $thumbnail,
                        'sold' => 0,
                        'is_active' => true,
                        'sale_off' => 0,
                    ]);

                    // Attach categories
                    $book->categories()->attach($categoryIds);

                    // Tạo BookImages nếu có
                    if (!empty($imageUrls)) {
                        $urls = array_map('trim', explode(';', $imageUrls));

                        foreach ($urls as $url) {
                            if (!empty($url)) {
                                BookImage::create([
                                    'book_id' => $book->id,
                                    'url' => $url,
                                ]);
                            }
                        }
                    }

                    // Load relationships để trả về
                    $book->load(['categories', 'bookImages']);
                    $importedBooks[] = $book;
                } catch (Exception $e) {
                    $errors[] = "Dòng {$rowNumber}: " . $e->getMessage();
                    continue;
                }
            }

            // Nếu có lỗi nhưng vẫn import được một số sách
            if (!empty($errors) && !empty($importedBooks)) {
                DB::commit();
                return response()->json([
                    'status' => 206, // Partial Content
                    'message' => 'Import hoàn tất với một số lỗi. Đã import ' . count($importedBooks) . ' cuốn sách.',
                    'data' => $importedBooks,
                    'errors' => $errors
                ], 206);
            }

            // Nếu có lỗi và không import được sách nào
            if (!empty($errors) && empty($importedBooks)) {
                DB::rollBack();
                return $this->errorResponse(
                    400,
                    'Import thất bại',
                    implode('; ', $errors)
                );
            }

            DB::commit();

            return $this->successResponse(
                200,
                'Import sách thành công! Đã import ' . count($importedBooks) . ' cuốn sách.',
                $importedBooks
            );
        } catch (Throwable $th) {
            DB::rollBack();
            return $this->errorResponse(
                500,
                'Lỗi khi import file Excel',
                $th->getMessage()
            );
        }
    }
}
