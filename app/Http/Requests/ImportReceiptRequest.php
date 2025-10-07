<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property int|null $id
 * @property string $receipt_number
 * @property string|null $notes
 * @property int $employee_id
 * @property array $details
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
            'id' => 'sometimes|integer|exists:import_receipts,id',

            'receipt_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('import_receipts', 'receipt_number')->ignore($this->id),
            ],

            'notes' => 'nullable|string',
            'employee_id' => 'required|exists:employees,id',

            'details' => 'required|array|min:1',
            'details.*.supply_id' => 'required|exists:supplies,id',
            'details.*.quantity' => 'required|integer|min:1',
        ];
    }
}
