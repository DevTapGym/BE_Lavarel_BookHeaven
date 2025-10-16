<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property int|null $id
 * @property string $receipt_number
 * @property string|null $notes
 * @property int $employeeEmail
 * @property array $importReceiptItems
 */
class ImportReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string',
            'employeeEmail' => 'required|email|exists:employees,email',
            'importReceiptItems' => 'required|array|min:1',
            'importReceiptItems.*.supplierId' => 'required|exists:suppliers,id',
            'importReceiptItems.*.bookId' => 'required|exists:books,id',
            'importReceiptItems.*.quantity' => 'required|integer|min:1',
        ];
    }
}
