<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;

use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{

    public function index()
    {
        $methods = PaymentMethod::all();
        return $this->successResponse(
            200,
            'Payment methods retrieved successfully',
            $methods
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'is_active' => 'boolean',
            'provider'  => 'nullable|string|max:255',
            'type'      => 'nullable|string|max:100',
            'logo_url'  => 'nullable|url',
        ]);

        $method = PaymentMethod::create($validated);

        return $this->successResponse(
            201,
            'Payment method created successfully',
            $method
        );
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'        => 'required|integer|exists:payment_methods,id',
            'name'      => 'sometimes|string|max:255',
            'is_active' => 'boolean',
            'provider'  => 'nullable|string|max:255',
            'type'      => 'nullable|string|max:100',
            'logo_url'  => 'nullable|url',
        ]);

        $method = PaymentMethod::find($validated['id']);
        $method->update($validated);

        return $this->successResponse(
            200,
            'Payment method updated successfully',
            $method
        );
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return $this->successResponse(
            200,
            'Payment method deleted successfully',
            null,
        );
    }
}
