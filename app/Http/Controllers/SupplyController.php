<?php

namespace App\Http\Controllers;

use App\Models\Supply;
use Illuminate\Http\Request;
use App\Http\Resources\SupplyResource;
use Illuminate\Validation\Rule;

class SupplyController extends Controller
{
    public function getByBookAndSupplier(Request $request)
    {
        $validated = $request->validate([
            'book_id'     => 'required|integer|exists:books,id',
            'supplier_id' => 'required|integer|exists:suppliers,id',
        ]);

        $supply = Supply::with(['book', 'supplier'])
            ->where('book_id', $validated['book_id'])
            ->where('supplier_id', $validated['supplier_id'])
            ->first();

        if (!$supply) {
            return $this->errorResponse(
                404,
                'Not found',
                'Supply not found'
            );
        }

        return $this->successResponse(
            200,
            'Supply retrieved successfully',
            new SupplyResource($supply)
        );
    }

    public function indexPaginated(Request $request)
    {
        $pageSize = $request->query('size', 10);
        $paginator = Supply::paginate($pageSize);
        $data = $this->paginateResponse($paginator);

        return $this->successResponse(
            200,
            'Supply retrieved successfully',
            $data
        );
    }

    public function store(Request $request)
    {
        $messages = [
            'supplier_id.unique' => 'This supplier has already provided this book.',
        ];

        $validated = $request->validate([
            'book_id'      => ['required', 'exists:books,id'],
            'supplier_id'  => [
                'required',
                'exists:suppliers,id',
                Rule::unique('supplies')->where(fn($q) => $q->where('book_id', $request->book_id)),
            ],
            'supply_price' => 'required|numeric|min:0',
        ], $messages);

        $supply = Supply::create($validated);

        return $this->successResponse(
            201,
            'Supply created successfully',
            new SupplyResource($supply->load(['book', 'supplier']))
        );
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'           => 'required|exists:supplies,id',
            'book_id'      => 'required|exists:books,id',
            'supplier_id'  => 'required|exists:suppliers,id',
            'supply_price' => 'required|numeric|min:0',
        ]);

        $supply = Supply::find($validated['id']);
        $supply->update($validated);

        return $this->successResponse(
            200,
            'Supply updated successfully',
            new SupplyResource($supply->load(['book', 'supplier']))
        );
    }

    public function destroy($id)
    {
        $supply = Supply::find($id);

        if (!$supply) {
            return $this->errorResponse(
                404,
                'Supply not found'
            );
        }

        $supply->delete();

        return $this->successResponse(
            200,
            'Supply deleted successfully',
            null
        );
    }
}
