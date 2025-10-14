<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderStatus;

class OrderStatusController extends Controller
{
    public function index()
    {
        $statuses = OrderStatus::all();
        return $this->successResponse(
            200,
            'Order status retrieved successfully',
            [
                'id' => $statuses->id,
                'status' => $statuses->name,
            ]
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'sequence'    => 'required|integer|min:0|unique:order_statuses,sequence',
        ]);

        $status = OrderStatus::create($validated);

        return $this->successResponse(
            201,
            'Order status created successfully',
            $status
        );
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'          => 'required|integer|exists:order_statuses,id',
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'sequence'    => 'sometimes|integer|min:0',
        ]);

        $status = OrderStatus::find($validated['id']);
        $status->update($validated);

        return $this->successResponse(
            200,
            'Order status updated successfully',
            $status
        );
    }

    public function destroy(OrderStatus $orderStatus)
    {
        $orderStatus->delete();
        return $this->successResponse(
            200,
            'Order status deleted successfully'
        );
    }
}
