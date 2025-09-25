<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Throwable;

class CartController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id|unique:carts,customer_id',
            ]);

            $validated['total_price'] = 0;
            $validated['count'] = 0;

            $cart = Cart::create($validated);

            return $this->successResponse(
                201,
                'Cart created successfully',
                $cart
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error creating cart',
                $th->getMessage()
            );
        }
    }

    public function getCartItemsByCustomer($customer_id)
    {
        try {
            $cart = Cart::with('cartItems')->where('customer_id', $customer_id)->first();
            if (! $cart) {
                return $this->errorResponse(404, 'Cart not found');
            }


            return $this->successResponse(
                200,
                'Cart items retrieved successfully',
                [
                    'total_items' => $cart->count,
                    'total_price' => $cart->total_price,
                    'items' => $cart->cartItems,
                ]
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error retrieving cart items',
                $th->getMessage()
            );
        }
    }


    public function addItemToCart(Request $request)
    {
        try {
            $validated = $request->validate([
                'cart_id' => 'required|exists:carts,id',
                'book_id' => 'required|exists:books,id',
                'quantity' => 'required|integer|min:1',
            ]);

            $book = Book::find($validated['book_id']);
            if (! $book) {
                return $this->errorResponse(404, 'Not Found', 'Book not found');
            }

            $cartItem = null;
            $cartId = $validated['cart_id'];

            DB::transaction(function () use ($validated, $book, $cartId, &$cartItem) {
                // Nếu đã có item giống (cùng book trong cùng cart) -> cộng dồn
                $existing = CartItem::where('cart_id', $cartId)
                    ->where('book_id', $validated['book_id'])
                    ->first();

                if ($existing) {
                    $existing->quantity = $existing->quantity + $validated['quantity'];
                    $existing->price = $book->price * $existing->quantity;
                    $existing->save();
                    $cartItem = $existing;
                } else {
                    $payload = [
                        'cart_id' => $cartId,
                        'book_id' => $validated['book_id'],
                        'quantity' => $validated['quantity'],
                        'price' => $book->price * $validated['quantity'],
                    ];
                    $cartItem = CartItem::create($payload);
                }

                $this->updateCartTotals($cartId);
            });

            return $this->successResponse(
                201,
                'Item added to cart successfully',
                $cartItem
            );
        } catch (Throwable $th) {
            return $this->errorResponse(500, 'Error adding item to cart', $th->getMessage());
        }
    }

    public function updateCartItem(Request $request, $cart_item_id)
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $cartItem = CartItem::findOrFail($cart_item_id);
            $book = Book::find($cartItem->book_id);
            if (! $book) {
                return $this->errorResponse(404, null, 'Book not found');
            }

            DB::transaction(function () use ($cartItem, $book, $validated) {
                $cartItem->quantity = $validated['quantity'];
                $cartItem->price = $book->price * $validated['quantity'];
                $cartItem->save();

                $this->updateCartTotals($cartItem->cart_id);
            });

            return $this->successResponse(200, 'Cart item updated successfully', $cartItem);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, null, 'Cart item not found');
        } catch (Throwable $th) {
            return $this->errorResponse(500, 'Error updating cart item', $th->getMessage());
        }
    }

    public function removeItemToCart($cart_item_id)
    {
        try {
            $cartItem = CartItem::findOrFail($cart_item_id);
            $cart_id = $cartItem->cart_id;

            $cartItem->delete();

            $this->updateCartTotals($cart_id);

            return $this->successResponse(
                200,
                'Item removed from cart successfully'
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error removing item from cart',
                $th->getMessage()
            );
        }
    }

    private function updateCartTotals($cart_id)
    {
        $cart = Cart::with('cartItems')->find($cart_id);

        if ($cart) {
            $cart->total_price = $cart->cartItems->sum('price');
            $cart->count = $cart->cartItems->count();
            $cart->save();
        }
    }
}
