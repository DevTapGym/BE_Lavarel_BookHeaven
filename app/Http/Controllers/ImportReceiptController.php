<?php

namespace App\Http\Controllers;

use App\Models\ImportReceipt;
use Illuminate\Http\Request;
use App\Http\Requests\ImportReceiptRequest;
use App\Http\Requests\ReturnImportReceiptRequest;
use Illuminate\Support\Facades\DB;
use App\Models\ImportReceiptDetail;
use App\Models\Supply;
use App\Models\Employee;
use App\Models\Book;
use App\Models\InventoryHistory;
use App\Http\Resources\ImportReceiptResource;
use Carbon\Carbon;
use Exception;

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
                
                // Tính giá vốn bình quân gia quyền
                // Công thức: Giá vốn mới = (Tồn kho cũ * Giá vốn cũ + SL nhập * Giá nhập) / (Tồn kho cũ + SL nhập)
                $oldQuantity = (float) $book->quantity;
                $oldCapitalPrice = (float) ($book->capital_price ?? 0);
                $importQuantity = (float) $detail['quantity'];
                $importPrice = (float) $price;

                $newQuantity = $oldQuantity + $importQuantity;
                $newCapitalPrice = 0;

                if ($newQuantity > 0) {
                    $newCapitalPrice = (($oldQuantity * $oldCapitalPrice) + ($importQuantity * $importPrice)) / $newQuantity;
                }

                InventoryHistory::create([
                    'book_id' => $book->id,
                    'code' => $importReceipt->receipt_number,
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

                // Cập nhật số lượng sách và giá vốn
                $book->increment('quantity', $detail['quantity']);
                $book->update(['capital_price' => $newCapitalPrice]);
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

    /**
     * Return import receipt (Trả hàng nhập)
     * Tham khảo từ returnOrder trong OrderController
     */
    public function returnImportReceipt($id, ReturnImportReceiptRequest $request)
    {
        try {
            return DB::transaction(function () use ($id, $request) {
                // Find the original import receipt
                $importReceipt = ImportReceipt::with(['importReceiptDetails', 'employee'])->find($id);
                if (!$importReceipt) {
                    return $this->errorResponse(404, 'Not Found', 'Import receipt not found');
                }

                // Create return import receipt with initial total_amount = 0
                $returnReceipt = new ImportReceipt();
                $returnReceipt->receipt_number = $this->generateReturnReceiptNumber($importReceipt);
                $returnReceipt->type = $request->receiptType ?? 'RETURN';
                $returnReceipt->employee_id = $request->employeeId ?? $importReceipt->employee_id;
                $returnReceipt->notes = $request->notes;
                $returnReceipt->parent_id = $importReceipt->id;
                $returnReceipt->total_amount = 0;
                $returnReceipt->save();

                $totalAmount = 0;
                $receiptDetails = [];

                // Process return items and calculate total amount
                if ($request->importReceiptItems && is_array($request->importReceiptItems)) {
                    foreach ($request->importReceiptItems as $item) {
                        // Find supply based on book_id and supplier_id
                        $supply = Supply::where('book_id', $item['bookId'])
                            ->where('supplier_id', $item['supplierId'])
                            ->first();

                        if (!$supply) {
                            return $this->errorResponse(404, 'Not Found', 'Supply not found');
                        }

                        $book = $supply->book;
                        if (!$book) {
                            return $this->errorResponse(404, 'Not Found', 'Book not found');
                        }

                        if (!isset($item['quantity'])) {
                            continue;
                        }

                        // Kiểm tra tồn kho trước khi trả
                        if ($book->quantity < $item['quantity']) {
                            return $this->errorResponse(
                                400,
                                'Bad Request',
                                "Insufficient stock for book: {$book->title}. Available: {$book->quantity}, Requested: {$item['quantity']}"
                            );
                        }

                        // Calculate price for this item and add to total
                        $itemPrice = $item['quantity'] * $supply->supply_price;
                        $totalAmount += $itemPrice;

                        // Create import receipt detail for return receipt
                        $receiptDetail = new ImportReceiptDetail();
                        $receiptDetail->import_receipt_id = $returnReceipt->id;
                        $receiptDetail->supply_id = $supply->id;
                        $receiptDetail->quantity = $item['quantity'];
                        $receiptDetail->price = $supply->supply_price;
                        $receiptDetail->save();

                        $receiptDetails[] = $receiptDetail;

                        // Update return_qty in original import receipt detail if provided
                        if (isset($item['importReceiptDetailId'])) {
                            $originReceiptDetail = ImportReceiptDetail::find($item['importReceiptDetailId']);
                            if ($originReceiptDetail) {
                                if (($originReceiptDetail->return_qty + $item['quantity']) > $originReceiptDetail->quantity) {
                                    return $this->errorResponse(
                                        400,
                                        'Bad Request',
                                        'Return quantity exceeds imported quantity for book ID: ' . $book->id
                                    );
                                }
                                $originReceiptDetail->return_qty += $item['quantity'];
                                $originReceiptDetail->save();
                            }
                        }

                        // Create inventory history for returning goods (OUT type - vì trả hàng cho nhà cung cấp)
                        InventoryHistory::create([
                            'book_id' => $book->id,
                            'code' => $returnReceipt->receipt_number,
                            'import_receipt_id' => $returnReceipt->id,
                            'type' => 'OUT',
                            'qty_stock_before' => $book->quantity,
                            'qty_change' => (int) $item['quantity'],
                            'qty_stock_after' => $book->quantity - $item['quantity'],
                            'price' => $book->price,
                            'total_price' => $item['quantity'] * $book->price,
                            'transaction_date' => now(),
                            'description' => 'Xuất kho do trả hàng nhập - ' . $returnReceipt->receipt_number,
                        ]);

                        // Update book inventory (trả hàng cho nhà cung cấp nên giảm tồn kho)
                        $book->decrement('quantity', $item['quantity']);

                        // Recalculate weighted average capital price
                        // Khi trả hàng, ta cần điều chỉnh lại giá vốn
                        // Giả sử trả hàng theo giá nhập cũ
                        $oldQuantity = (float) ($book->quantity + $item['quantity']);
                        $oldCapitalPrice = (float) ($book->capital_price ?? 0);
                        $returnQuantity = (float) $item['quantity'];
                        $returnPrice = (float) $supply->supply_price;

                        $newQuantity = $oldQuantity - $returnQuantity;
                        $newCapitalPrice = 0;

                        if ($newQuantity > 0) {
                            // Tính lại giá vốn sau khi trừ đi số lượng trả
                            $totalOldValue = $oldQuantity * $oldCapitalPrice;
                            $returnValue = $returnQuantity * $returnPrice;
                            $newCapitalPrice = ($totalOldValue - $returnValue) / $newQuantity;
                        }

                        $book->update(['capital_price' => max(0, $newCapitalPrice)]);
                    }
                }

                // Handle return fee
                if ($request->returnFee) {
                    $returnReceipt->return_fee = $request->returnFee;
                    $returnReceipt->return_fee_type = $request->returnFeeType ?? 'value';

                    if ($request->returnFeeType === 'percent') {
                        $totalAmount -= ($totalAmount * ($request->returnFee / 100));
                    } else {
                        $totalAmount -= $request->returnFee;
                    }
                }

                // Set total refund amount
                $returnReceipt->total_refund_amount = $totalAmount;
                $returnReceipt->total_amount = $totalAmount;
                $returnReceipt->save();

                // Load relationships
                $returnReceipt->load(['importReceiptDetails.supply.book', 'employee']);

                return $this->successResponse(
                    201,
                    'Return import receipt created successfully',
                    new ImportReceiptResource($returnReceipt)
                );
            });
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Error creating return import receipt',
                $e->getMessage()
            );
        }
    }

    /**
     * Generate return receipt number based on parent receipt
     * Example: RN161025001-TH01, RN161025001-TH02
     */
    private function generateReturnReceiptNumber(ImportReceipt $importReceipt)
    {
        $parentReceiptId = $importReceipt->id;
        $childReceipts = ImportReceipt::where('parent_id', $parentReceiptId)->get();

        $returnCount = $childReceipts->count() + 1;

        return $importReceipt->receipt_number . '-TH' . str_pad($returnCount, 2, '0', STR_PAD_LEFT);
    }
}
