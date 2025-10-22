<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class OrderReportController extends Controller
{
    /**
     * Báo cáo đơn hàng theo thời gian
     * GET /api/v1/reports/orders
     */
    public function reportByOrder(Request $request)
    {
        try {
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
            $groupBy = $request->input('group_by', 'day'); 
            $userId = $request->input('user_id', null); 

            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

    
            $query = Order::query();

 
            $query->whereBetween('created_at', [$start, $end]);

            $query->whereIn('status', ['completed', 'returned']); 
            $query->where('payment_status', '1'); 

  
            $query->orderBy('created_at', 'asc');

            $orders = $query->with(['orderItems.book'])->get();


            $reportMap = [];
            
            foreach ($orders as $order) {
                $reportDateKey = $this->getReportDateKey($order->created_at, $groupBy);
                
                if (!isset($reportMap[$reportDateKey])) {
                $reportMap[$reportDateKey] = [
                    'key' => $reportDateKey,
                    'total_return' => 0,
                    'net_revenue' => 0,
                    'total_amount' => 0,
                    'total' => 0,
                    'total_discount' => 0,
                    'total_discount_return' => 0,
                    'total_capital_price' => 0,
                    'total_profit' => 0,
                    'total_order' => 0,
                ];
                }

                $item = &$reportMap[$reportDateKey];

                // Calculate order totals
                $discount = $order->total_promotion_value ?? 0;
                $total = $order->total_amount;
                $totalCapitalPrice = $this->calculateOrderCapitalPrice($order);

                if ($order->status == 'returned') { // Return order
                    $item['total'] += 0;
                    $item['total_return'] += $total + $discount;
                    $item['total_capital_price'] -= $totalCapitalPrice;
                    $item['total_discount_return'] += $discount;
                } else { // Complete order
                    $profit = $total - $discount - $totalCapitalPrice;
                    $item['total'] += $total;
                    $item['total_discount'] += $discount;
                    $item['total_return'] += 0;
                    $item['total_capital_price'] += $totalCapitalPrice;
                    $item['total_amount'] += $total + $discount;
                    $item['total_profit'] += $profit;
                    $item['total_order'] += 1;
                }

                $item['net_revenue'] = $item['total'] - $item['total_return'] + $item['total_discount_return'];
                $item['total_profit'] = $item['net_revenue'] - $item['total_capital_price'];
            }


            $reportItems = array_values($reportMap);
            usort($reportItems, function($a, $b) {
                return strcmp($a['key'], $b['key']);
            });
            $summary = $this->calculateSummary($reportItems);

            return $this->successResponse(200, 'Lấy báo cáo đơn hàng thành công', [
                'summary' => $summary,
                'period' => [
                    'start_date' => $start->format('Y-m-d'),
                    'end_date' => $end->format('Y-m-d'),
                    'group_by' => $groupBy,
                ],
                'report_items' => $reportItems,
            ]);

        } catch (\Throwable $th) {
            return $this->errorResponse(500, 'Lỗi khi lấy báo cáo đơn hàng', $th->getMessage());
        }
    }

    /**
     * Export báo cáo đơn hàng ra Excel
     * GET /api/v1/reports/orders/export
     */
    public function exportOrderReport(Request $request)
    {
        try {
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
            $groupBy = $request->input('group_by', 'day');

            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Báo cáo đơn hàng');

            // Title
            $sheet->setCellValue('A1', 'BÁO CÁO ĐƠN HÀNG');
            $sheet->mergeCells('A1:I1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A2', 'Từ ngày: ' . $start->format('d/m/Y') . ' - Đến ngày: ' . $end->format('d/m/Y'));
            $sheet->mergeCells('A2:I2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Headers
            $headers = [
                'Ngày',
                'Số đơn',
                'Tổng tiền',
                'Giảm giá',
                'Doanh thu thuần',
                'Trả hàng',
                'Giá vốn',
                'Lợi nhuận',
                'Tỷ suất LN (%)'
            ];

            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '4', $header);
                $sheet->getStyle($col . '4')->getFont()->setBold(true);
                $sheet->getStyle($col . '4')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');
                $col++;
            }

            // Get report data
            $reportData = $this->getOrderReportData($start, $end, $groupBy);

            // Fill data
            $row = 5;
            foreach ($reportData as $item) {
                $profitMargin = $item['net_revenue'] > 0 
                    ? ($item['total_profit'] / $item['net_revenue']) * 100 
                    : 0;

                $sheet->setCellValue('A' . $row, $item['key']);
                $sheet->setCellValue('B' . $row, $item['total_order']);
                $sheet->setCellValue('C' . $row, $item['total']);
                $sheet->setCellValue('D' . $row, $item['total_discount']);
                $sheet->setCellValue('E' . $row, $item['net_revenue']);
                $sheet->setCellValue('F' . $row, $item['total_return']);
                $sheet->setCellValue('G' . $row, $item['total_capital_price']);
                $sheet->setCellValue('H' . $row, $item['total_profit']);
                $sheet->setCellValue('I' . $row, round($profitMargin, 2) . '%');
                $row++;
            }

            // Total row
            $totalRow = $row;
            $sheet->setCellValue('A' . $totalRow, 'TỔNG CỘNG');
            $sheet->setCellValue('B' . $totalRow, "=SUM(B5:B" . ($totalRow - 1) . ")");
            $sheet->setCellValue('C' . $totalRow, "=SUM(C5:C" . ($totalRow - 1) . ")");
            $sheet->setCellValue('D' . $totalRow, "=SUM(D5:D" . ($totalRow - 1) . ")");
            $sheet->setCellValue('E' . $totalRow, "=SUM(E5:E" . ($totalRow - 1) . ")");
            $sheet->setCellValue('F' . $totalRow, "=SUM(F5:F" . ($totalRow - 1) . ")");
            $sheet->setCellValue('G' . $totalRow, "=SUM(G5:G" . ($totalRow - 1) . ")");
            $sheet->setCellValue('H' . $totalRow, "=SUM(H5:H" . ($totalRow - 1) . ")");
            
            $sheet->getStyle('A' . $totalRow . ':I' . $totalRow)->getFont()->setBold(true);
            $sheet->getStyle('A' . $totalRow . ':I' . $totalRow)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFFCE4D6');

            // Auto size columns
            foreach (range('A', 'I') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $fileName = 'bao_cao_don_hang_' . date('Y-m-d_H-i-s') . '.xlsx';
            $writer = new Xlsx($spreadsheet);
            
            $temp_file = tempnam(sys_get_temp_dir(), 'excel_');
            $writer->save($temp_file);
            $fileContent = file_get_contents($temp_file);
            unlink($temp_file);

            return response($fileContent, 200)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
                ->header('Cache-Control', 'max-age=0');

        } catch (\Throwable $th) {
            return $this->errorResponse(500, 'Lỗi khi export báo cáo đơn hàng', $th->getMessage());
        }
    }

    /**
     * Báo cáo chi tiết đơn hàng
     * GET /api/v1/reports/orders/detail
     */
    public function orderDetailReport(Request $request)
    {
        try {
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
            $orderStatus = $request->input('order_status', null); 
            $limit = $request->input('limit', 100);

            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            $query = Order::with(['customer', 'orderItems.book'])
                ->whereBetween('created_at', [$start, $end])
                ->where('payment_status', '1');

            if ($orderStatus != null) {
                $query->where('status', $orderStatus);
            }

            $orders = $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($order) {
                    $totalCapitalPrice = $this->calculateOrderCapitalPrice($order);
                    $discount = $order->total_promotion_value ?? 0;
                    $profit = $order->total_amount - $totalCapitalPrice;

                    return [
                        'id' => $order->id,
                        'order_code' => $order->order_number ?? 'ORD-' . $order->id,
                        'customer_name' => $order->customer->name ?? 'N/A',
                        'customer_phone' => $order->customer->phone ?? 'N/A',
                        'order_date' => $order->created_at->format('Y-m-d H:i:s'),
                        'status' => $order->status ?? 'N/A',
                        'status_id' => $order->status,
                        'total_amount' => $order->total_amount,
                        'discount_total' => $discount,
                        'capital_price' => $totalCapitalPrice,
                        'profit' => $profit,
                        'profit_margin' => $order->total_amount > 0 ? ($profit / $order->total_amount) * 100 : 0,
                        'items_count' => $order->orderItems->count(),
                    ];
                });

            // Summary statistics
            $summary = [
                'total_orders' => $orders->count(),
                'total_amount' => $orders->sum('total_amount'),
                'total_discount' => $orders->sum('discount_total'),
                'total_profit' => $orders->sum('profit'),
                'average_order_value' => $orders->avg('total_amount'),
                'complete_orders' => $orders->where('status_id', 'completed')->count(),
                'return_orders' => $orders->where('status_id', 'returned')->count(),
            ];

            return $this->successResponse(200, 'Lấy báo cáo chi tiết đơn hàng thành công', [
                'summary' => $summary,
                'orders' => $orders->values(),
            ]);

        } catch (\Throwable $th) {
            return $this->errorResponse(500, 'Lỗi khi lấy báo cáo chi tiết đơn hàng', $th->getMessage());
        }
    }

    // ==================== HELPER METHODS ====================

    private function getReportDateKey($date, $groupBy)
    {
        $carbon = Carbon::parse($date);
        
        return match($groupBy) {
            'day' => $carbon->format('Y-m-d'),
            'week' => $carbon->format('Y-W'),
            'month' => $carbon->format('Y-m'),
            'year' => $carbon->format('Y'),
            default => $carbon->format('Y-m-d'),
        };
    }

    private function calculateOrderCapitalPrice($order)
    {
        return $order->orderItems->sum('total_capital_price');
    }

    private function calculateSummary($reportItems)
    {
        return [
            'total_orders' => array_sum(array_column($reportItems, 'total_order')),
            'total_amount' => array_sum(array_column($reportItems, 'total')),
            'total_discount' => array_sum(array_column($reportItems, 'total_discount')),
            'net_revenue' => array_sum(array_column($reportItems, 'net_revenue')),
            'total_return' => array_sum(array_column($reportItems, 'total_return')),
            'total_capital_price' => array_sum(array_column($reportItems, 'total_capital_price')),
            'total_profit' => array_sum(array_column($reportItems, 'total_profit')),
            'profit_margin_percent' => array_sum(array_column($reportItems, 'net_revenue')) > 0 
                ? (array_sum(array_column($reportItems, 'total_profit')) / array_sum(array_column($reportItems, 'net_revenue'))) * 100 
                : 0,
        ];
    }

    private function getOrderReportData($start, $end, $groupBy)
    {
            $query = Order::query()
                ->whereBetween('created_at', [$start, $end])
                ->whereIn('status', ['completed', 'returned'])
                ->where('payment_status', 'completed')
                ->orderBy('created_at', 'asc');

        $orders = $query->with(['orderItems.book'])->get();

        $reportMap = [];
        
        foreach ($orders as $order) {
            $reportDateKey = $this->getReportDateKey($order->created_at, $groupBy);
            
            if (!isset($reportMap[$reportDateKey])) {
                $reportMap[$reportDateKey] = [
                    'key' => $reportDateKey,
                    'total_return' => 0,
                    'net_revenue' => 0,
                    'total_amount' => 0,
                    'total' => 0,
                    'total_discount' => 0,
                    'total_capital_price' => 0,
                    'total_profit' => 0,
                    'total_order' => 0,
                ];
            }

            $item = &$reportMap[$reportDateKey];

            $discount = $order->total_promotion_value ?? 0;
            $total = $order->total_amount - $discount;
            $totalCapitalPrice = $this->calculateOrderCapitalPrice($order);

            if ($order->status == 'returned') { // Return
                $item['total_return'] += $total;
                $item['total_capital_price'] -= $totalCapitalPrice;
                $item['total_discount'] -= $discount;
            } else { // Complete
                $profit = $total - $discount - $totalCapitalPrice;
                $item['total'] += $total;
                $item['total_discount'] += $discount;
                $item['total_capital_price'] += $totalCapitalPrice;
                $item['total_amount'] += $total;
                $item['total_profit'] += $profit;
                $item['total_order'] += 1;
            }

            $item['net_revenue'] = $item['total_amount'] - $item['total_return'];
            $item['total_profit'] = $item['net_revenue'] - $item['total_capital_price'];
        }

        $reportItems = array_values($reportMap);
        usort($reportItems, function($a, $b) {
            return strcmp($a['key'], $b['key']);
        });

        return $reportItems;
    }

    private function checkAdminPermission()
    {
        // Implement your admin permission check logic here
        // For now, return true (admin has all permissions)
        return true;
    }
}
