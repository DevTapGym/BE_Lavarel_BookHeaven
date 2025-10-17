<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReturnOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'receiverName' => 'nullable|string',
            'email' => 'nullable|string',
            'receiverAddress' => 'nullable|string',
            'receiverPhone' => 'nullable|string',
            'paymentMethod' => 'nullable|string',
            'customerId' => 'nullable|integer',
            'promotionId' => 'nullable|integer',
            'totalPrice' => 'nullable|numeric',
            'orderType' => 'nullable|string',
            'totalPromotionValue' => 'nullable|numeric',
            'statusId' => 'nullable|integer',
            'returnFee' => 'nullable|numeric',
            'returnFeeType' => 'nullable|string|in:percent,value',
            'orderItems' => 'nullable|array',
            'orderItems.*.bookId' => 'nullable|integer',
            'orderItems.*.quantity' => 'nullable|integer',
            'orderItems.*.orderItemId' => 'nullable|integer',
        ];
    }
}
