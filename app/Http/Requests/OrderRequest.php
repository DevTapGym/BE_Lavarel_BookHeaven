<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Models\Book;

/**
 * @property float $total_amount
 * @property string|null $note
 * @property float $shipping_fee
 * @property int $shipping_address_id
 * @property int $payment_method_id
 * @property array $items
 */
class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Order fields
            'note'                 => 'nullable|string|max:500',
            'shipping_fee'         => 'required|numeric|min:0',
            'shipping_address_id'  => 'required|exists:shipping_addresses,id',
            'payment_method_id'    => 'required|exists:payment_methods,id',
            'promotion_id'         => 'nullable|exists:promotions,id',

            // Order items
            'items'                => 'required|array|min:1',
            'items.*.book_id'      => 'required|exists:books,id',
            'items.*.quantity'     => 'required|integer|min:1',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (isset($this->items) && is_array($this->items)) {
                $this->validateBookItems($validator);
            }
        });
    }

    /**
     * Validate book items availability and stock
     */
    private function validateBookItems(Validator $validator): void
    {
        foreach ($this->items as $index => $item) {
            if (!isset($item['book_id']) || !isset($item['quantity'])) {
                continue; // Skip if basic validation already failed
            }

            $book = Book::find($item['book_id']);

            if (!$book) {
                $validator->errors()->add(
                    "items.{$index}.book_id",
                    "Book with ID {$item['book_id']} does not exist"
                );
                continue;
            }

            if (!$book->is_active) {
                $validator->errors()->add(
                    "items.{$index}.book_id",
                    "Book '{$book->title}' is no longer available for sale"
                );
            }

            if ($book->quantity < $item['quantity']) {
                $validator->errors()->add(
                    "items.{$index}.quantity",
                    "Book '{$book->title}' has only {$book->quantity} copies left, not enough required quantity ({$item['quantity']} copies)"
                );
            }
        }
    }
}
