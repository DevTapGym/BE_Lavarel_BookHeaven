<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string|null $notes
 * @property int|null $employeeId
 * @property float|null $totalAmount
 * @property string|null $receiptType
 * @property int|null $statusId
 * @property float|null $returnFee
 * @property string|null $returnFeeType
 * @property array|null $importReceiptItems
 */
class ReturnImportReceiptRequest extends FormRequest
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
            'notes' => 'nullable|string',
            'employeeId' => 'nullable|integer|exists:employees,id',
            'totalAmount' => 'nullable|numeric',
            'receiptType' => 'nullable|string',
            'statusId' => 'nullable|integer',
            'returnFee' => 'nullable|numeric',
            'returnFeeType' => 'nullable|string|in:percent,value',
            'importReceiptItems' => 'nullable|array',
            'importReceiptItems.*.bookId' => 'required|integer|exists:books,id',
            'importReceiptItems.*.supplierId' => 'required|integer|exists:suppliers,id',
            'importReceiptItems.*.quantity' => 'required|integer|min:1',
            'importReceiptItems.*.importReceiptDetailId' => 'nullable|integer|exists:import_receipt_details,id',
        ];
    }
}

