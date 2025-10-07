<?php

namespace App\Http\Controllers;

use App\Models\ImportReceipt;
use Illuminate\Http\Request;
use App\Http\Requests\ImportReceiptRequest;
use Illuminate\Support\Facades\DB;
use App\Models\ImportReceiptDetail;
use App\Models\Supply;
use App\Http\Resources\ImportReceiptResource;

class ImportReceiptController extends Controller
{
    public function indexPaginated(Request $request)
    {
        $pageSize = $request->query('size', 10);
        $paginator = ImportReceipt::paginate($pageSize);
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

    public function store(ImportReceiptRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $totalAmount = collect($request->details)->sum('price');

            $importReceipt = ImportReceipt::create([
                'receipt_number' => $request->receipt_number,
                'notes' => $request->notes,
                'employee_id' => $request->employee_id,
                'total_amount' => $totalAmount,
            ]);

            foreach ($request->details as $detail) {
                $supply = Supply::findOrFail($detail['supply_id']);
                $price = $supply->supply_price;

                ImportReceiptDetail::create([
                    'import_receipt_id' => $importReceipt->id,
                    'supply_id' => $detail['supply_id'],
                    'quantity' => $detail['quantity'],
                    'price' => $price,
                ]);

                // cập nhật số lượng sách
                $book = $supply->book;
                $book->increment('quantity', $detail['quantity']);
            }

            $importReceipt->load('importReceiptDetails');

            return $this->successResponse(
                201,
                'Import Receipt created successfully',
                $importReceipt
            );
        });
    }

    public function update(ImportReceiptRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $importReceipt = ImportReceipt::findOrFail($request->id);

            $totalAmount = collect($request->details)->sum('total_price');

            $importReceipt->update([
                'receipt_number' => $request->receipt_number,
                'notes' => $request->notes,
                'employee_id' => $request->employee_id,
                'total_amount' => $totalAmount,
            ]);

            // Lấy chi tiết cũ để rollback số lượng sách
            $oldDetails = ImportReceiptDetail::where('import_receipt_id', $importReceipt->id)->get();

            foreach ($oldDetails as $oldDetail) {
                $supply = Supply::find($oldDetail->supply_id);
                if ($supply && $supply->book) {
                    $supply->book->decrement('quantity', $oldDetail->quantity);
                }
            }

            // Xóa chi tiết cũ
            ImportReceiptDetail::where('import_receipt_id', $importReceipt->id)->delete();

            // Tạo lại chi tiết mới + cộng số lượng sách
            foreach ($request->details as $detail) {
                $supply = Supply::findOrFail($detail['supply_id']);
                $price = $supply->supply_price;

                ImportReceiptDetail::create([
                    'import_receipt_id' => $importReceipt->id,
                    'supply_id' => $detail['supply_id'],
                    'quantity' => $detail['quantity'],
                    'price' => $price,
                ]);

                // cập nhật số lượng sách
                $book = $supply->book;
                $book->increment('quantity', $detail['quantity']);
            }

            $importReceipt->load('importReceiptDetails');

            return $this->successResponse(
                200,
                'Import Receipt updated successfully',
                $importReceipt
            );
        });
    }
}
