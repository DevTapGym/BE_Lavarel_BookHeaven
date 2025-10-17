<?php

namespace App\Http\Controllers;

use App\Models\ImportReceipt;
use Illuminate\Http\Request;
use App\Http\Requests\ImportReceiptRequest;
use Illuminate\Support\Facades\DB;
use App\Models\ImportReceiptDetail;
use App\Models\Supply;
use App\Models\Employee;
use App\Models\Book;
use App\Models\InventoryHistory;
use App\Http\Resources\ImportReceiptResource;
use Carbon\Carbon;

class ImportReceiptController extends Controller
{
    public function indexPaginated(Request $request)
    {
        $pageSize = $request->query('size', 10);
        $paginator = ImportReceipt::with(['employee', 'importReceiptDetails.supply.book'])
            ->paginate($pageSize);

        $paginator->setCollection(
            collect(ImportReceiptResource::collection($paginator->items()))
        );
        $data = $this->paginateResponse($paginator);

        return $this->successResponse(
            200,
            'Import Receipt retrieved successfully',
            $data
        );
    }

    public function show($id)
    {
        $importReceipt = ImportReceipt::with(['employee', 'importReceiptDetails.supply.book', 'importReceiptDetails.supply.supplier'])
            ->findOrFail($id);

        return $this->successResponse(
            200,
            'Import Receipt retrieved successfully',
            new ImportReceiptResource($importReceipt)
        );
    }

    /**
     * Tạo mã phiếu nhập theo cấu trúc: RN + ngày(2 số) + tháng(2 số) + năm(2 số cuối) + số thứ tự trong ngày(3 số)
     * Ví dụ: RN161025001 (16/10/2025, phiếu thứ 1 trong ngày)
     */
    private function generateReceiptNumber()
    {
        // RN + Ngày hiện tại
        $today = Carbon::now();
        $datePrefix = 'RN' . $today->format('dmy');

        // Đếm số phiếu nhập đã tạo trong ngày hôm nay
        $todayStart = $today->startOfDay();
        $todayEnd = $today->copy()->endOfDay();
        $count = ImportReceipt::whereBetween('created_at', [$todayStart, $todayEnd])->count();

        // Số thứ tự (3 chữ số)
        $sequenceNumber = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        return $datePrefix . $sequenceNumber;
    }

    public function store(ImportReceiptRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $employee = Employee::where('email', $request->employeeEmail)->firstOrFail();

            $receiptNumber = $this->generateReceiptNumber();

            $totalAmount = 0;
            foreach ($request->importReceiptItems as $detail) {
                // Tìm supply dựa trên book_id và supplier_id
                $supply = Supply::where('book_id', $detail['bookId'])
                    ->where('supplier_id', $detail['supplierId'])
                    ->firstOrFail();

                $totalAmount += $supply->supply_price * $detail['quantity'];
            }

            // Tạo phiếu nhập
            $importReceipt = ImportReceipt::create([
                'receipt_number' => $receiptNumber,
                'notes' => $request->notes,
                'employee_id' => $employee->id,
                'total_amount' => $totalAmount,
                'created_by' => $request->employeeEmail,
            ]);

            foreach ($request->importReceiptItems as $detail) {
                // Tìm supply dựa trên book_id và supplier_id
                $supply = Supply::where('book_id', $detail['bookId'])
                    ->where('supplier_id', $detail['supplierId'])
                    ->firstOrFail();

                $price = $supply->supply_price;

                $book = $supply->book;
                
                ImportReceiptDetail::create([
                    'import_receipt_id' => $importReceipt->id,
                    'supply_id' => $supply->id,
                    'quantity' => $detail['quantity'],
                    'price' => $price,
                ]);
                
    
                InventoryHistory::create([
                    'book_id' => $book->id,
                    'import_receipt_id' => $importReceipt->id,
                    'type' => 'IN',
                    'qty_stock_before' => $book->quantity,
                    'qty_change' => (int) $detail['quantity'],
                    'qty_stock_after' => $book->quantity + $detail['quantity'],
                    'price' => $book->price,
                    'total_price' => $detail['quantity'] * $book->price,
                    'transaction_date' => now(),
                    'description' => 'Nhập kho - ' . $importReceipt->receipt_number,
                ]);

                // Cập nhật số lượng sách
                $book->increment('quantity', $detail['quantity']);
            }

            $importReceipt->load('importReceiptDetails.supply.book');

            return $this->successResponse(
                201,
                'Import Receipt created successfully',
                $importReceipt
            );
        });
    }

    // public function update(ImportReceiptRequest $request)
    // {
    //     return DB::transaction(function () use ($request) {
    //         $importReceipt = ImportReceipt::findOrFail($request->id);

    //         $totalAmount = collect($request->details)->sum('total_price');

    //         $importReceipt->update([
    //             'receipt_number' => $request->receipt_number,
    //             'notes' => $request->notes,
    //             //'employee_id' => $request->employee_id,
    //             'total_amount' => $totalAmount,
    //         ]);

    //         // Lấy chi tiết cũ để rollback số lượng sách
    //         $oldDetails = ImportReceiptDetail::where('import_receipt_id', $importReceipt->id)->get();

    //         foreach ($oldDetails as $oldDetail) {
    //             $supply = Supply::find($oldDetail->supply_id);
    //             if ($supply && $supply->book) {
    //                 $supply->book->decrement('quantity', $oldDetail->quantity);
    //             }
    //         }

    //         // Xóa chi tiết cũ
    //         ImportReceiptDetail::where('import_receipt_id', $importReceipt->id)->delete();

    //         // Tạo lại chi tiết mới + cộng số lượng sách
    //         foreach ($request->details as $detail) {
    //             $supply = Supply::findOrFail($detail['supply_id']);
    //             $price = $supply->supply_price;

    //             ImportReceiptDetail::create([
    //                 'import_receipt_id' => $importReceipt->id,
    //                 'supply_id' => $detail['supply_id'],
    //                 'quantity' => $detail['quantity'],
    //                 'price' => $price,
    //             ]);

    //             // cập nhật số lượng sách
    //             $book = $supply->book;
    //             $book->increment('quantity', $detail['quantity']);
    //         }

    //         $importReceipt->load('importReceiptDetails');

    //         return $this->successResponse(
    //             200,
    //             'Import Receipt updated successfully',
    //             $importReceipt
    //         );
    //     });
    // }
}
