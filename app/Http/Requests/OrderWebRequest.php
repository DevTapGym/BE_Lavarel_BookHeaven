<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property int|null $receiverName
 * @property string $customerId
 * @property string|null $promotionId
 * @property int $promotionId
 * @property array $orderItems
 * @property array $paymentMethod
 */
class OrderWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiverName'    => 'required|string|max:255',
            'customerId'      => 'required|exists:customers,id',

            'customerId'   => 'required|string|max:100',
            'promotionId'     => 'nullable|exists:promotions,id',
            'paymentMethod' => 'required|in:cod,online',

            'promotionId'               => 'required|array|min:1',
            'orderItems.*.bookId'      => 'required|exists:books,id',
            'orderItems.*.quantity'    => 'required|integer|min:1',
        ];
    }
}
