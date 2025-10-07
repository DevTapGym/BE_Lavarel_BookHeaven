<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderStatusHistory;
use App\Models\OrderStatus;
use App\Models\Order;

class OrderStatusHistoryController extends Controller
{
    public function indexByOrder($orderId)
    {
        // Kiểm tra xem order có tồn tại không
        $orderExists = Order::where('id', $orderId)->exists();

        if (!$orderExists) {
            return $this->errorResponse(
                404,
                'Not Found',
                'Order not found'
            );
        }

        $histories = OrderStatusHistory::with('orderStatus')
            ->where('order_id', $orderId)
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->successResponse(
            200,
            'Order status histories retrieved successfully',
            $histories
        );
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_status_id' => 'required|exists:order_statuses,id',
            'note' => 'nullable|string'
        ]);

        $orderId = $validated['order_id'];
        $status = OrderStatus::findOrFail($validated['order_status_id']);

        // Lấy sequence cao nhất đã có trong lịch sử order này
        $maxSequence = OrderStatusHistory::where('order_id', $orderId)
            ->join('order_statuses', 'order_status_histories.order_status_id', '=', 'order_statuses.id')
            ->max('order_statuses.sequence') ?? 0;

        // Check Cancelled (sequence = 5)
        if ($status->sequence == 5 && $status->name === 'Cancelled') {
            // Nếu chưa tồn tại Cancelled cho order thì cho thêm
            $existsCancelled = OrderStatusHistory::where('order_id', $orderId)
                ->where('order_status_id', $status->id)
                ->exists();
            if ($existsCancelled) {
                return $this->errorResponse(422, 'Cancelled status already exists for this order');
            }
        } else {
            // Phải đúng thứ tự liền kề
            if ($status->sequence != $maxSequence + 1) {
                return $this->errorResponse(
                    422,
                    'Unprocessable Entity',
                    "Invalid sequence. Must be " . ($maxSequence + 1)
                );
            }
        }

        $history = OrderStatusHistory::create($validated);

        return $this->successResponse(201, 'History created successfully', $history);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'    => 'required|integer|exists:order_status_histories,id',
            'note'  => 'nullable|string',
        ]);

        $history = OrderStatusHistory::findOrFail($validated['id']);
        $history->update($validated);

        return $this->successResponse(
            200,
            'Order status history updated successfully',
            $history
        );
    }

    public function destroy(OrderStatusHistory $orderStatusHistory)
    {
        $orderStatusHistory->delete();
        return $this->successResponse(
            200,
            'Order status history deleted successfully',
            null
        );
    }
}
