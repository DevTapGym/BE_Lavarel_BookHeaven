<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property int $id
 * @property string|null $notes
 * @property array $importReceiptItems
 */
class UpdateImportReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:import_receipts,id',
            'notes' => 'nullable|string',
            'importReceiptItems' => 'required|array|min:1',
            'importReceiptItems.*.supplierId' => 'required|exists:suppliers,id',
            'importReceiptItems.*.bookId' => 'required|exists:books,id',
            'importReceiptItems.*.quantity' => 'required|integer|min:1',
        ];
    }
}

