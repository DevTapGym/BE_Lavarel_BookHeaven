<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Models\Book;

/**
 * @property string|null $note
 * @property string $payment_method
 * @property string $phone
 * @property string $address
 * @property string $name
 * @property array $items
 * @property int|null $promotion_id
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
            'payment_method'       => 'required|string',
            'phone'                => 'required|string|max:20',
            'address'              => 'required|string|max:500',
            'name'                 => 'required|string|max:255',
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
