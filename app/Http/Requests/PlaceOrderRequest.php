<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Models\CartItem;
use App\Models\Book;

/**
 * @property string|null $note
 * @property string $payment_method
 * @property int $cart_id
 * @property string $phone
 * @property string $address
 * @property string $name
 */
class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note'                 => 'nullable|string|max:500',
            'payment_method'       => 'required|string',
            'cart_id'              => 'required|exists:carts,id',
            'phone'                => 'required|string|max:20',
            'address'              => 'required|string|max:500',
            'name'                 => 'required|string|max:255',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (isset($this->cart_id)) {
                $this->validateSelectedCartItems($validator);
            }
        });
    }

    /**
     * Validate selected cart items
     */
    private function validateSelectedCartItems(Validator $validator): void
    {
        $selectedItems = CartItem::where('cart_id', $this->cart_id)
            ->where('is_selected', true)
            ->with('book')
            ->get();

        if ($selectedItems->isEmpty()) {
            $validator->errors()->add('cart_id', 'No products selected in the cart');
            return;
        }

        foreach ($selectedItems as $index => $item) {
            $book = $item->book;

            if (!$book) {
                $validator->errors()->add(
                    "items.{$index}",
                    "Product does not exist"
                );
                continue;
            }

            if (!$book->is_active) {
                $validator->errors()->add(
                    "items.{$index}",
                    "Book '{$book->title}' is no longer available for sale"
                );
            }

            if ($book->quantity < $item->quantity) {
                $validator->errors()->add(
                    "items.{$index}",
                    "Book '{$book->title}' has only {$book->quantity} copies left, not enough required quantity ({$item->quantity} copies)"
                );
            }
        }
    }
}
