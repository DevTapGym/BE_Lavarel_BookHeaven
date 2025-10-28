<?php

namespace App\Http\Controllers;

use App\Http\Resources\InventoryHistoryResource;
use App\Models\InventoryHistory;
use Illuminate\Http\Request;

class InventoryHistoryController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->query('size', 10);
        $query = InventoryHistory::query();

        if ($request->filled('bookId')) {
            $query->where('book_id', $request->query('bookId'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        // Filter by date range: startDate and endDate
        if ($request->filled('startDate')) {
            $query->whereDate('transaction_date', '>=', $request->query('startDate'));
        }

        if ($request->filled('endDate')) {
            $query->whereDate('transaction_date', '<=', $request->query('endDate'));
        }

        // filter=name~'keyword' to search by book title (via relation)
        $rawFilter = $request->query('filter');
        if (!empty($rawFilter)) {
            $decoded = urldecode($rawFilter);
            if (preg_match("/([^~]+)~'([^']*)'/", $decoded, $matches)) {
                $filterValue = $matches[2];
                $query->whereHas('book', function ($q) use ($filterValue) {
                    $q->where('title', 'like', "%{$filterValue}%");
                });
            }
        }

        // sort=transactionDate,desc
        $sortParam = $request->query('sort');
        if (!empty($sortParam)) {
            [$sortField, $sortDir] = array_pad(explode(',', $sortParam, 2), 2, 'asc');
            $sortDir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';
            $fieldMap = [
                'transactionDate' => 'transaction_date',
                'createdAt' => 'created_at',
                'updatedAt' => 'updated_at',
            ];
            $dbField = $fieldMap[$sortField] ?? $sortField;
            $query->orderBy($dbField, $sortDir);
        } else {
            // Default sort by created_at descending
            $query->orderBy('created_at', 'desc');
        }

        $paginator = $query->with(['book'])->paginate($pageSize);
        $data = $this->paginateResponse($paginator);
        $data['result'] = InventoryHistoryResource::collection($paginator->getCollection());

        return $this->successResponse(200, 'Fetch list inventory history successfully', $data);
    }

    public function stats(Request $request)
    {
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $openingQty = 0;
        $openingValue = 0.0;

        if (!empty($startDate)) {
            $before = InventoryHistory::query()
                ->whereDate('transaction_date', '<', $startDate)
                ->get();
            foreach ($before as $row) {
                $isIn = in_array($row->type, ['IN', 'IMPORT']);
                $isOut = in_array($row->type, ['OUT', 'SALE', 'EXPORT']);
                $qty = abs((int) ($row->qty_change ?? 0));
                $val = abs((float) ($row->total_price ?? 0));
                if ($isIn) {
                    $openingQty += $qty;
                    $openingValue += $val;
                } elseif ($isOut) {
                    $openingQty -= $qty;
                    $openingValue -= $val;
                }
            }
        }

        if (!empty($startDate) && !empty($endDate)) {
            $filter = InventoryHistory::query()
                ->whereDate('transaction_date', '>=', $startDate)
                ->whereDate('transaction_date', '<=', $endDate)
                ->get();

            $importQty = 0.0; $importValue = 0.0; $exportQty = 0.0; $exportValue = 0.0;
            foreach ($filter as $row) {
                $isIn = in_array($row->type, ['IN', 'IMPORT']);
                $isOut = in_array($row->type, ['OUT', 'SALE', 'EXPORT']);
                $qty = abs((float) ($row->qty_change ?? 0));
                $val = abs((float) ($row->total_price ?? 0));
                if ($isIn) {
                    $importQty += $qty;
                    $importValue += $val;
                } elseif ($isOut) {
                    $exportQty += $qty;
                    $exportValue += $val;
                }
            }

            $response = [
                'openingQty' => (int) $openingQty,
                'openingValue' => (float) $openingValue,
                'importQty' => (int) $importQty,
                'importValue' => (float) $importValue,
                'exportQty' => (int) $exportQty,
                'exportValue' => (float) $exportValue,
                'closingQty' => (int) ($openingQty + $importQty - $exportQty),
                'closingValue' => (float) ($openingValue + $importValue - $exportValue),
            ];
        } else {
            $all = InventoryHistory::all();
            $importQty = 0.0; $importValue = 0.0; $exportQty = 0.0; $exportValue = 0.0;
            foreach ($all as $row) {
                $isIn = in_array($row->type, ['IN', 'IMPORT']);
                $isOut = in_array($row->type, ['OUT', 'SALE', 'EXPORT']);
                $qty = abs((float) ($row->qty_change ?? 0));
                $val = abs((float) ($row->total_price ?? 0));
                if ($isIn) {
                    $importQty += $qty;
                    $importValue += $val;
                } elseif ($isOut) {
                    $exportQty += $qty;
                    $exportValue += $val;
                }
            }

            $response = [
                'openingQty' => 0,
                'openingValue' => 0.0,
                'importQty' => (int) $importQty,
                'importValue' => (float) $importValue,
                'exportQty' => (int) $exportQty,
                'exportValue' => (float) $exportValue,
                'closingQty' => (int) (0 + $importQty - $exportQty),
                'closingValue' => (float) (0.0 + $importValue - $exportValue),
            ];
        }

        return $this->successResponse(200, 'Fetch inventory history stats successfully', $response);
    }
}


