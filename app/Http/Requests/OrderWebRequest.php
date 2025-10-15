<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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

            'paymentMethod'   => 'required|string|max:100',
            'promotionId'     => 'nullable|exists:promotions,id',

            'orderItems'               => 'required|array|min:1',
            'orderItems.*.bookId'      => 'required|exists:books,id',
            'orderItems.*.quantity'    => 'required|integer|min:1',
        ];
    }
}


