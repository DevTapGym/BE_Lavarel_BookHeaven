<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class SupplierController extends Controller
{
    public function indexPaginated(Request $request)
    {
        $pageSize = $request->query('size', 10);

        $suppliers = QueryBuilder::for(Supplier::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::partial('address'),
                AllowedFilter::partial('email'),
            ])
            ->allowedSorts(['created_at', 'address', 'phone', 'name', 'email', 'updated_at'])
            ->defaultSort('-created_at')
            ->paginate($pageSize);

        $data = $this->paginateResponse($suppliers);

        return $this->successResponse(
            200,
            'Supplier retrieved successfully',
            $data
        );
    }

    public function index()
    {
        $suppliers = Supplier::all();

        return $this->successResponse(
            200,
            'Supplier retrieved successfully',
            $suppliers
        );
    }

    public function show(Supplier $supplier)
    {
        return $this->successResponse(
            200,
            'Supplier retrieved successfully',
            $supplier
        );
    }

    public function getBooksBySupplier($id)
    {
        $supplier = Supplier::with('books')->findOrFail($id);

        return $this->successResponse(
            200,
            'Books retrieved successfully',
            $supplier->books
        );
    }

    public function getSuppliesBySupplier($id)
    {
        $supplier = Supplier::with(['supplies.book'])->find($id);
        if (!$supplier) {
            return $this->errorResponse(
                404,
                'Supplier not found'
            );
        }

        return $this->successResponse(
            200,
            'Supplies retrieved successfully',
            $supplier->supplies
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:20',
        ]);

        $supplier = Supplier::create($validated);

        return $this->successResponse(
            201,
            'Supplier created successfully',
            $supplier
        );
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'      => 'required|integer|exists:suppliers,id',
            'name'    => 'sometimes|required|string|max:255',
            'address' => 'nullable|string|max:500',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:20',
        ]);

        $supplier = Supplier::findOrFail($validated['id']);
        $supplier->update($validated);

        return $this->successResponse(
            200,
            'Supplier updated successfully',
            $supplier
        );
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return $this->successResponse(
            200,
            'Supplier deleted successfully',
            null
        );
    }
}
