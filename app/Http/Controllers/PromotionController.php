<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Http\Resources\PromotionResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class PromotionController extends Controller
{
    public function indexPaginated(Request $request)
    {
        $pageSize = $request->query('size', 10);
        $query = Promotion::query()->where('is_deleted', 0);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $rawFilter = $request->query('filter');
        if (!empty($rawFilter)) {
            $decoded = urldecode($rawFilter);
            if (preg_match("/([^~]+)~'([^']*)'/", $decoded, $matches)) {
                $filterValue = $matches[2];
                $query->where(function ($q) use ($filterValue) {
                    $q->where('name', 'like', "%{$filterValue}%")
                      ->orWhere('code', 'like', "%{$filterValue}%");
                });
            }
        }

        $sortParam = $request->query('sort');
        if (!empty($sortParam)) {
            [$sortField, $sortDir] = array_pad(explode(',', $sortParam, 2), 2, 'asc');
            $sortDir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';
            // Map camelCase to snake_case fields used in DB
            $fieldMap = [
                'createdAt' => 'created_at',
                'updatedAt' => 'updated_at',
                'startDate' => 'start_date',
                'endDate' => 'end_date',
                'promotionType' => 'promotion_type',
                'promotionValue' => 'promotion_value',
                'maxPromotionValue' => 'max_promotion_value',
                'orderMinValue' => 'order_min_value',
                'qtyLimit' => 'qty_limit',
                'isOncePerCustomer' => 'is_once_per_customer',
                'isMaxPromotionValue' => 'is_max_promotion_value',
            ];
            $dbField = $fieldMap[$sortField] ?? $sortField; // allow name/code/status
            $query->orderBy($dbField, $sortDir);
        }

        $paginator = $query->paginate($pageSize);
        $data = $this->paginateResponse($paginator);
        $data['result'] = PromotionResource::collection($paginator->getCollection());

        return $this->successResponse(200, 'Fetch all promotions successfully', $data);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => ['nullable', 'string', 'max:50', Rule::unique('promotions', 'code')],
                'name' => ['required', 'string', 'max:255'],
                'status' => ['nullable', 'string', 'max:50'],
                'promotionType' => ['nullable', 'string', 'max:50'],
                'promotionValue' => ['nullable', 'numeric'],
                'isMaxPromotionValue' => ['boolean'],
                'maxPromotionValue' => ['nullable', 'numeric'],
                'orderMinValue' => ['nullable', 'numeric'],
                'startDate' => ['nullable', 'date'],
                'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
                'qtyLimit' => ['nullable', 'integer', 'min:0'],
                'isOncePerCustomer' => ['boolean'],
                'note' => ['nullable', 'string'],
            ]);
    
            if (empty($validated['code'])) {
                $validated['code'] = $this->generateCodePromotion();
            }
    
            // map camelCase -> snake_case để lưu DB
            $payload = [
                'code' => $validated['code'],
                'name' => $validated['name'],
                'status' => $validated['status'] ?? null,
                'promotion_type' => $validated['promotionType'] ?? null,
                'promotion_value' => $validated['promotionValue'] ?? null,
                'is_max_promotion_value' => $validated['isMaxPromotionValue'] ?? false,
                'max_promotion_value' => $validated['maxPromotionValue'] ?? null,
                'order_min_value' => $validated['orderMinValue'] ?? null,
                'start_date' => $validated['startDate'] ?? null,
                'end_date' => $validated['endDate'] ?? null,
                'qty_limit' => $validated['qtyLimit'] ?? null,
                'is_once_per_customer' => $validated['isOncePerCustomer'] ?? false,
                'note' => $validated['note'] ?? null,
            ];
    
            $promotion = Promotion::create($payload);
    
            return $this->successResponse(201, 'Create promotion successfully', new PromotionResource($promotion));
        } catch (Throwable $th) {
            return $this->errorResponse(500, $th->getMessage(), 'Error creating promotion');
        }
    }
    public function show(Promotion $promotion)
    {
        return $this->successResponse(200, 'Fetch promotion successfully', new PromotionResource($promotion));
    }

    public function update(Request $request, Promotion $promotion)
    {
        try {
            $validated = $request->validate([
                'code' => ['nullable', 'string', 'max:50', Rule::unique('promotions', 'code')->ignore($promotion->id)],
                'name' => ['sometimes', 'string', 'max:255'],
                'status' => ['sometimes', 'string', 'max:50'],
                'promotionType' => ['sometimes', 'string', 'max:50'],
                'promotionValue' => ['sometimes', 'numeric'],
                'isMaxPromotionValue' => ['sometimes', 'boolean'],
                'maxPromotionValue' => ['nullable', 'numeric'],
                'orderMinValue' => ['nullable', 'numeric'],
                'startDate' => ['nullable', 'date'],
                'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
                'qtyLimit' => ['nullable', 'integer', 'min:0'],
                'isOncePerCustomer' => ['sometimes', 'boolean'],
                'note' => ['nullable', 'string'],
            ]);
    
            if (array_key_exists('code', $validated) && empty($validated['code'])) {
                $validated['code'] = $this->generateCodePromotion();
            }
    
            $payload = [];
            foreach ([
                'code' => 'code',
                'name' => 'name',
                'status' => 'status',
                'promotionType' => 'promotion_type',
                'promotionValue' => 'promotion_value',
                'isMaxPromotionValue' => 'is_max_promotion_value',
                'maxPromotionValue' => 'max_promotion_value',
                'orderMinValue' => 'order_min_value',
                'startDate' => 'start_date',
                'endDate' => 'end_date',
                'qtyLimit' => 'qty_limit',
                'isOncePerCustomer' => 'is_once_per_customer',
                'note' => 'note',
            ] as $in => $out) {
                if (array_key_exists($in, $validated)) {
                    $payload[$out] = $validated[$in];
                }
            }
    
            $promotion->update($payload);
    
            return $this->successResponse(200, 'Update promotion successfully', new PromotionResource($promotion));
        } catch (Throwable $th) {
            return $this->errorResponse(500, $th->getMessage(), 'Error updating promotion');
        }
    }
    public function destroy(Promotion $promotion)
    {
        $promotion->update([
            'is_deleted' => 1,
            'deleted_at' => now(),
        ]);

        return $this->successResponse(200, 'Delete promotion successfully');
    }

    private function generateCodePromotion(): string
    {
        $length = 6;
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
}


