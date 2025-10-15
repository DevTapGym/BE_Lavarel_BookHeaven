<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\Book;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Exception;

class DashBoardController extends Controller
{
    public function getStats()
    {
        try {
            $totalOrders = Order::count();
            $totalUsers = User::count();

            $totalBooks = Book::count();
            $totalCustomers = Customer::count();
            $totalEmployees = Employee::count();
            $activeUsers = User::where('is_active', true)->count();
            $inactiveUsers = User::where('is_active', false)->count();

            // Orders by status (if you want more detailed order stats)
            $pendingOrders = Order::whereHas('statusHistories', function ($query) {
                $query->whereHas('orderStatus', function ($statusQuery) {
                    $statusQuery->where('sequence', 1); // Assuming sequence 1 is pending
                });
            })->count();

            $completedOrders = Order::whereHas('statusHistories', function ($query) {
                $query->whereHas('orderStatus', function ($statusQuery) {
                    $statusQuery->where('name', 'Delivered')
                        ->where('sequence', 4);
                });
            })->count();
            // Recent orders (last 30 days)            
            $recentOrders = Order::where('created_at', '>=', now()->subDays(30))->count();

            // Total revenue (sum of all delivered orders)
            $totalRevenue = Order::whereHas('statusHistories', function ($query) {
                $query->whereHas('orderStatus', function ($statusQuery) {
                    $statusQuery->where('name', 'Delivered')
                        ->where('sequence', 4);
                });
            })->sum('total_amount');

            $stats = [
                'overview' => [
                    'total_orders' => $totalOrders,
                    'total_users' => $totalUsers,
                    'total_books' => $totalBooks,
                    'total_customers' => $totalCustomers,
                    'total_employees' => $totalEmployees,
                ],
                'users' => [
                    'total_users' => $totalUsers,
                    'active_users' => $activeUsers,
                    'inactive_users' => $inactiveUsers,
                ],
                'orders' => [
                    'total_orders' => $totalOrders,
                    'pending_orders' => $pendingOrders,
                    'completed_orders' => $completedOrders,
                    'recent_orders' => $recentOrders,
                ],
                'revenue' => [
                    'total_revenue' => $totalRevenue,
                    'average_order_value' => $completedOrders > 0 ? round($totalRevenue / $completedOrders, 2) : 0,
                ],
                'timestamp' => now()->toISOString(),
            ];

            return $this->successResponse(
                200,
                'Dashboard statistics retrieved successfully',
                $stats
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                'Failed to retrieve dashboard statistics: ' . $e->getMessage()
            );
        }
    }

    public function getBasicCounts()
    {
        try {
            $totalOrders = Order::count();
            $totalUsers = User::count();

            $data = [
                'total_orders' => $totalOrders,
                'total_users' => $totalUsers,
                'timestamp' => now()->toISOString(),
            ];

            return $this->successResponse(
                200,
                'Basic counts retrieved successfully',
                $data
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                'Failed to retrieve basic counts: ' . $e->getMessage()
            );
        }
    }

    public function getMonthlyRevenue(Request $request)
    {
        try {
            $year = (int) $request->input('year', now()->year);

            if ($year < 2020 || $year > now()->year + 1) {
                return $this->errorResponse(
                    400,
                    'Bad Request',
                    'Year must be between 2020 and ' . (now()->year + 1)
                );
            }

            $monthlyRevenue = [];
            $totalYearRevenue = 0;
            $totalYearOrders = 0;

            for ($month = 1; $month <= 12; $month++) {
                $startOfMonth = now()->create($year, $month, 1)->startOfMonth();
                $endOfMonth = now()->create($year, $month, 1)->endOfMonth();

                $monthRevenue = Order::whereHas('statusHistories', function ($query) {
                    $query->whereHas('orderStatus', function ($statusQuery) {
                        $statusQuery->where('name', 'Delivered')
                            ->where('sequence', 4);
                    });
                })
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->sum('total_amount');


                $monthOrderCount = Order::whereHas('statusHistories', function ($query) {
                    $query->whereHas('orderStatus', function ($statusQuery) {
                        $statusQuery->where('name', 'Delivered')
                            ->where('sequence', 4);
                    });
                })
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->count();

                $monthlyRevenue[] = [
                    'month' => $month,
                    'month_name' => $startOfMonth->format('F'),
                    'revenue' => (float) $monthRevenue,
                    'order_count' => $monthOrderCount,
                    'average_order_value' => $monthOrderCount > 0 ? round($monthRevenue / $monthOrderCount, 2) : 0
                ];

                $totalYearRevenue += $monthRevenue;
                $totalYearOrders += $monthOrderCount;
            }

            $data = [
                'year' => $year,
                'monthly_data' => $monthlyRevenue,
                'summary' => [
                    'total_revenue' => (float) $totalYearRevenue,
                    'total_orders' => $totalYearOrders,
                    'average_monthly_revenue' => round($totalYearRevenue / 12, 2),
                    'average_order_value' => $totalYearOrders > 0 ? round($totalYearRevenue / $totalYearOrders, 2) : 0,
                    'best_month' => collect($monthlyRevenue)->sortByDesc('revenue')->first(),
                    'worst_month' => collect($monthlyRevenue)->sortBy('revenue')->first()
                ],
                'timestamp' => now()->toISOString()
            ];

            return $this->successResponse(
                200,
                "Monthly revenue for year {$year} retrieved successfully",
                $data
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                'Failed to retrieve monthly revenue: ' . $e->getMessage()
            );
        }
    }

    public function view9()
    {
        try {
            // Lấy thống kê số lượng theo giới tính từ bảng customers
            $rawStats = Customer::select('gender', DB::raw('COUNT(*) as total'))
                ->whereIn('gender', ['Male', 'Female', 'Other'])
                ->groupBy('gender')
                ->pluck('total', 'gender'); // returns associative collection

            $mapping = [
                'Male' => 'Nam',
                'Female' => 'Nữ',
                'Other' => 'Khác',
            ];

            $totalAll = $rawStats->sum();

            $result = [];
            foreach ($mapping as $key => $label) {
                $count = (int) ($rawStats[$key] ?? 0);
                $percentage = $totalAll > 0 ? round($count / $totalAll * 100, 2) : 0;
                // Nếu phần trăm là số nguyên (vd 33.00) thì có thể cast về float giữ 2 decimal tuỳ yêu cầu FE
                $result[] = [
                    'gender' => $label,
                    'genderCount' => $count,
                    'percentage' => $percentage,
                ];
            }

            return $this->successResponse(
                200,
                'Get view9 success',
                $result
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                'Failed to get gender statistics: ' . $e->getMessage()
            );
        }
    }

    public function view6()
    {
        try {
            $tongSoLuongKhachHang = Customer::count();
            $tongSoLuongSach = Book::count();
            $tongSoLuongNhanVien = Employee::count();
            $tongSoLuongDatHangThanhCong = Order::whereHas('statusHistories', function ($query) {
                $query->whereHas('orderStatus', function ($statusQuery) {
                    $statusQuery->where('name', 'Delivered')->where('sequence', 4);
                });
            })->count();

            $data = [
                'tongSoLuongKhachHang' => $tongSoLuongKhachHang,
                'tongSoLuongSach' => $tongSoLuongSach,
                'tongSoLuongNhanVien' => $tongSoLuongNhanVien,
                'tongSoLuongDatHangThanhCong' => $tongSoLuongDatHangThanhCong,
            ];

            return $this->successResponse(
                200,
                'Get view6 success',
                $data
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                'Failed to get view6: ' . $e->getMessage()
            );
        }
    }

    public function view1()
    {
        try {
            // Lấy tất cả tên thể loại từ DB
            $categories = DB::table('categories')
                ->pluck('name')
                ->toArray();

            // Lấy số lượng sách bán ra theo từng thể loại
            $soldByCategory = DB::table('categories')
                ->select('categories.name as theLoai', DB::raw('COALESCE(SUM(order_items.quantity), 0) as soLuongBan'))
                ->leftJoin('book_category', 'categories.id', '=', 'book_category.category_id')
                ->leftJoin('books', 'book_category.book_id', '=', 'books.id')
                ->leftJoin('order_items', 'books.id', '=', 'order_items.book_id')
                ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
                ->leftJoin('order_status_histories', 'orders.id', '=', 'order_status_histories.order_id')
                ->leftJoin('order_statuses', 'order_status_histories.order_status_id', '=', 'order_statuses.id')
                ->where('order_statuses.name', 'Delivered')
                ->where('order_statuses.sequence', 4)
                ->whereIn('categories.name', $categories)
                ->groupBy('categories.name')
                ->pluck('soLuongBan', 'theLoai');

            // Đảm bảo trả về đủ các thể loại, kể cả khi không có đơn hàng
            $result = [];
            foreach ($categories as $cat) {
                $result[] = [
                    'theLoai' => $cat,
                    'soLuongBan' => (int)($soldByCategory[$cat] ?? 0)
                ];
            }

            return $this->successResponse(
                200,
                'Get view1 success',
                $result
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                'Failed to get view1: ' . $e->getMessage()
            );
        }
    }


    public function getTopCategoriesByYear(Request $request)
    {
        try {
            // Validate year parameter
            $year = (int) $request->input('year', now()->year);

            // Validate year range (reasonable range)
            if ($year < 2000 || $year > now()->year + 1) {
                return $this->errorResponse(
                    400,
                    'Bad Request',
                    'Year must be between 2000 and ' . (now()->year + 1)
                );
            }

            // Get start and end of year
            $startOfYear = now()->create($year, 1, 1)->startOfYear();
            $endOfYear = now()->create($year, 12, 31)->endOfYear();

            // Query to get top 5 categories with most sold books
            $topCategories = Category::select(
                'categories.id',
                'categories.name',
                'categories.description'
            )
                ->selectRaw('SUM(order_items.quantity) as total_quantity_sold')
                ->selectRaw('COUNT(DISTINCT orders.id) as total_orders')
                ->selectRaw('SUM(order_items.quantity * order_items.price) as total_revenue')
                ->join('book_category', 'categories.id', '=', 'book_category.category_id')
                ->join('books', 'book_category.book_id', '=', 'books.id')
                ->join('order_items', 'books.id', '=', 'order_items.book_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('order_status_histories', 'orders.id', '=', 'order_status_histories.order_id')
                ->join('order_statuses', 'order_status_histories.order_status_id', '=', 'order_statuses.id')
                ->where('order_statuses.name', 'Delivered')
                ->where('order_statuses.sequence', 4)
                ->whereBetween('orders.created_at', [$startOfYear, $endOfYear])
                ->groupBy('categories.id', 'categories.name', 'categories.description')
                ->orderByDesc('total_quantity_sold')
                ->limit(5)
                ->get();

            // Calculate total sold quantity for percentage calculation
            $totalSoldInYear = Category::join('book_category', 'categories.id', '=', 'book_category.category_id')
                ->join('books', 'book_category.book_id', '=', 'books.id')
                ->join('order_items', 'books.id', '=', 'order_items.book_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('order_status_histories', 'orders.id', '=', 'order_status_histories.order_id')
                ->join('order_statuses', 'order_status_histories.order_status_id', '=', 'order_statuses.id')
                ->where('order_statuses.name', 'Delivered')
                ->where('order_statuses.sequence', 4)
                ->whereBetween('orders.created_at', [$startOfYear, $endOfYear])
                ->sum('order_items.quantity');

            // Format the response data
            $formattedCategories = $topCategories->map(function ($category, $index) use ($totalSoldInYear) {
                return [
                    'rank' => $index + 1,
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'description' => $category->description,
                    'total_quantity_sold' => (int) $category->total_quantity_sold,
                    'total_orders' => (int) $category->total_orders,
                    'total_revenue' => (float) $category->total_revenue,
                    'percentage_of_total_sales' => $totalSoldInYear > 0 ?
                        round(($category->total_quantity_sold / $totalSoldInYear) * 100, 2) : 0,
                    'average_quantity_per_order' => $category->total_orders > 0 ?
                        round($category->total_quantity_sold / $category->total_orders, 2) : 0,
                ];
            });

            $data = [
                'year' => $year,
                'top_categories' => $formattedCategories,
                'summary' => [
                    'total_categories_tracked' => $topCategories->count(),
                    'total_quantity_sold_all_categories' => (int) $totalSoldInYear,
                    'top_5_quantity_sold' => (int) $topCategories->sum('total_quantity_sold'),
                    'top_5_percentage_of_total' => $totalSoldInYear > 0 ?
                        round(($topCategories->sum('total_quantity_sold') / $totalSoldInYear) * 100, 2) : 0,
                ],
                'period' => [
                    'start_date' => $startOfYear->toDateString(),
                    'end_date' => $endOfYear->toDateString(),
                ],
                'timestamp' => now()->toISOString()
            ];

            return $this->successResponse(
                200,
                "Top 5 best-selling categories for year {$year} retrieved successfully",
                $data
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                'Failed to retrieve top categories: ' . $e->getMessage()
            );
        }
    }

    public function getTopBooksByYear(Request $request)
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

            $startOfYear = now()->create($year, 1, 1)->startOfYear();
            $endOfYear = now()->create($year, 12, 31)->endOfYear();

            $topBooks = Book::select(
                'books.id',
                'books.title',
                'books.author',
                'books.price',
                'books.thumbnail',
                'books.description'
            )
                ->selectRaw('SUM(order_items.quantity) as total_quantity_sold')
                ->selectRaw('COUNT(DISTINCT orders.id) as total_orders')
                ->selectRaw('SUM(order_items.quantity * order_items.price) as total_revenue')
                ->join('order_items', 'books.id', '=', 'order_items.book_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('order_status_histories', 'orders.id', '=', 'order_status_histories.order_id')
                ->join('order_statuses', 'order_status_histories.order_status_id', '=', 'order_statuses.id')
                ->where('order_statuses.name', 'Delivered')
                ->where('order_statuses.sequence', 4)
                ->whereBetween('orders.created_at', [$startOfYear, $endOfYear])
                ->groupBy('books.id', 'books.title', 'books.author', 'books.price', 'books.thumbnail', 'books.description')
                ->orderByDesc('total_quantity_sold')
                ->limit(5)
                ->get();

            // Calculate total books sold in year for percentage calculation
            $totalBooksSoldInYear = Book::join('order_items', 'books.id', '=', 'order_items.book_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('order_status_histories', 'orders.id', '=', 'order_status_histories.order_id')
                ->join('order_statuses', 'order_status_histories.order_status_id', '=', 'order_statuses.id')
                ->where('order_statuses.name', 'Delivered')
                ->where('order_statuses.sequence', 4)
                ->whereBetween('orders.created_at', [$startOfYear, $endOfYear])
                ->sum('order_items.quantity');

            // Format the response data
            $formattedBooks = $topBooks->map(function ($book, $index) use ($totalBooksSoldInYear) {
                return [
                    'rank' => $index + 1,
                    'book_id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'price' => (float) $book->price,
                    'thumbnail' => $book->thumbnail,
                    'description' => $book->description,
                    'total_quantity_sold' => (int) $book->total_quantity_sold,
                    'total_orders' => (int) $book->total_orders,
                    'total_revenue' => (float) $book->total_revenue,
                    'percentage_of_total_sales' => $totalBooksSoldInYear > 0 ?
                        round(($book->total_quantity_sold / $totalBooksSoldInYear) * 100, 2) : 0,
                    'average_quantity_per_order' => $book->total_orders > 0 ?
                        round($book->total_quantity_sold / $book->total_orders, 2) : 0,
                    'average_price_per_unit' => $book->total_quantity_sold > 0 ?
                        round($book->total_revenue / $book->total_quantity_sold, 2) : 0,
                ];
            });

            $data = [
                'year' => $year,
                'top_books' => $formattedBooks,
                'summary' => [
                    'total_books_tracked' => $topBooks->count(),
                    'total_quantity_sold_all_books' => (int) $totalBooksSoldInYear,
                    'top_5_quantity_sold' => (int) $topBooks->sum('total_quantity_sold'),
                    'top_5_revenue' => (float) $topBooks->sum('total_revenue'),
                    'top_5_percentage_of_total' => $totalBooksSoldInYear > 0 ?
                        round(($topBooks->sum('total_quantity_sold') / $totalBooksSoldInYear) * 100, 2) : 0,
                    'best_selling_book' => $formattedBooks->first(),
                ],
                'period' => [
                    'start_date' => $startOfYear->toDateString(),
                    'end_date' => $endOfYear->toDateString(),
                ],
                'timestamp' => now()->toISOString()
            ];

            return $this->successResponse(
                200,
                "Top 5 best-selling books for year {$year} retrieved successfully",
                $data
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                'Failed to retrieve top books: ' . $e->getMessage()
            );
        }
    }
}
