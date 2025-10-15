<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Book;
use App\Models\User;
use App\Models\ShippingAddress;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\PlaceOrderRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use Carbon\Carbon;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderForWebResource;
use Illuminate\Validation\ValidationException;
use Exception;



class OrderController extends Controller
{
    /**
     * Generate unique order number: ORDER + DDMMYYYY + XXXX
     */
    private function generateOrderNumber()
    {
        $today = Carbon::now();
        $datePrefix = $today->format('dmY');

        $todayOrdersCount = Order::whereDate('created_at', $today->toDateString())->count();

        $orderSequence = str_pad($todayOrdersCount + 1, 4, '0', STR_PAD_LEFT);

        return "ORDER{$datePrefix}{$orderSequence}";
    }

    public function indexPaginated(Request $request)
    {
        $pageSize = $request->query('size', 10);
        $paginator = Order::paginate($pageSize);
        $data = $this->paginateResponse($paginator);

        return $this->successResponse(
            200,
            'Order retrieved successfully',
            $data
        );
    }

    public function show(Order $order)
    {
        // Load tất cả các quan hệ cần thiết
        $order->load([
            'orderItems.book',
            'shippingAddress.customer',
            'shippingAddress.tag',
            'paymentMethod',
            'statusHistories.orderStatus'
        ]);

        return $this->successResponse(
            200,
            'Order retrieved successfully',
            new OrderResource($order)
        );
    }

    public function getOrdersByUser(Request $request)
    {
        $pageSize = $request->query('size', 10);

        $user = Auth::user();

        if (!$user || !$user->customer_id) {
            return $this->errorResponse(
                404,
                'Not Found',
                'User not found or user is not a customer'
            );
        }

        // Lấy tất cả đơn hàng của user thông qua customer_id
        $orders = Order::whereHas('shippingAddress', function ($query) use ($user) {
            $query->where('customer_id', $user->customer_id);
        })
            ->with([
                'orderItems.book',
                'shippingAddress.customer',
                'shippingAddress.tag',
                'paymentMethod',
                'statusHistories.orderStatus'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($pageSize);

        // Transform collection using OrderResource
        $transformedOrders = $orders->getCollection()->map(function ($order) {
            return new OrderResource($order);
        });

        // Replace the collection in paginator
        $orders->setCollection($transformedOrders);

        $data = $this->paginateResponse($orders);

        return $this->successResponse(
            200,
            'User orders retrieved successfully',
            $data
        );
    }

    public function getOrdersByUserForWeb($userId)
    {
        try {
            $user = User::find($userId);
            if (!$user || !$user->customer_id) {
                return $this->errorResponse(
                    404,
                    'Not Found',
                    'User not found or user is not a customer'
                );
            }

            // Lấy tất cả đơn hàng của user thông qua customer_id
            $orders = Order::whereHas('shippingAddress', function ($query) use ($user) {
                $query->where('customer_id', $user->customer_id);
            })
                ->with([
                    'orderItems.book.categories.books.bookImages',
                    'orderItems.book.bookImages',
                    'shippingAddress.customer.user',
                    'shippingAddress.customer.cart.cartItems.book.categories.books.bookImages',
                    'shippingAddress.customer.cart.cartItems.book.bookImages',
                    'paymentMethod',
                    'statusHistories.orderStatus'
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse(
                200,
                'User orders retrieved successfully',
                OrderForWebResource::collection($orders)
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Error retrieving user orders',
                $e->getMessage()
            );
        }
    }


    public function createOrder(OrderRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $orderNumber = $this->generateOrderNumber();

            // Tính tổng tiền dựa trên các item thực tế
            $totalAmount = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $book = Book::find($item['book_id']);

                if (!$book) {
                    return $this->errorResponse(
                        404,
                        'Not Found',
                        "Book with ID {$item['book_id']} not found"
                    );
                }

                if ($book->quantity < $item['quantity']) {
                    return $this->errorResponse(
                        400,
                        'Bad Request',
                        "Insufficient stock for book: {$book->title}. Available: {$book->quantity}, Requested: {$item['quantity']}"
                    );
                }

                $itemTotal = $book->price * $item['quantity'];
                $totalAmount += $itemTotal;

                $orderItems[] = [
                    'book' => $book,
                    'quantity' => $item['quantity'],
                    'price' => $book->price,
                    'book_id' => $item['book_id']
                ];
            }

            $orderData = [
                'order_number'        => $orderNumber,
                'total_amount'        => $totalAmount,
                'note'                => $request->note,
                'shipping_fee'        => $request->shipping_fee ?? 0,
                'shipping_address_id' => $request->shipping_address_id,
                'payment_method_id'   => $request->payment_method_id,
            ];

            $order = Order::create($orderData);

            // Tạo order items và cập nhật stock
            foreach ($orderItems as $item) {
                $order->orderItems()->create([
                    'book_id'  => $item['book_id'],
                    'quantity' => $item['quantity'],
                    'price'    => $item['price'],
                ]);

                $item['book']->decrement('quantity', $item['quantity']);
                $item['book']->increment('sold', $item['quantity']);
            }

            // Tạo trạng thái đơn hàng ban đầu
            $this->createInitialOrderStatus($order->id, 'Order created successfully');

            $order->load(['orderItems.book', 'shippingAddress', 'paymentMethod', 'statusHistories.orderStatus']);

            return $this->successResponse(
                201,
                'Order created successfully',
                $order
            );
        });
    }

    public function createOrderForWeb(Request $request)
    {
        try {
            $validated = $request->validate([
                'accountId'     => 'required|integer',
                'email'         => 'required|email',
                'name'          => 'required|string|max:255',
                'address'       => 'required|string|max:500',
                'phone'         => 'required|string|max:20',
                'totalPrice'    => 'required|numeric|min:0',
                'paymentMethod' => 'required|string|in:cod,banking',
            ]);

            return DB::transaction(function () use ($validated) {
                // Tìm user và customer
                $user = User::where('email', $validated['email'])->first();
                if (!$user || !$user->customer_id) {
                    return $this->errorResponse(
                        404,
                        'Not Found',
                        'Customer not found'
                    );
                }

                $customerId = $user->customer_id;

                $shippingAddress = ShippingAddress::create([
                    'customer_id'    => $customerId,
                    'recipient_name' => $validated['name'],
                    'address'        => $validated['address'],
                    'phone_number'   => $validated['phone'],
                    'is_default'     => false,
                    'tag_id'         => 1,
                ]);

                // Lấy cart của user
                $cart = Cart::where('customer_id', $customerId)->first();
                if (!$cart) {
                    return $this->errorResponse(
                        404,
                        'Not Found',
                        'Cart not found'
                    );
                }

                // Lấy các items đã chọn trong cart
                $selectedCartItems = CartItem::where('cart_id', $cart->id)
                    ->where('is_selected', true)
                    ->with('book')
                    ->get();

                if ($selectedCartItems->isEmpty()) {
                    return $this->errorResponse(
                        400,
                        'Bad Request',
                        'No selected items in the cart'
                    );
                }

                // Kiểm tra stock và tính tổng tiền
                $totalAmount = 0;
                $validatedItems = [];

                foreach ($selectedCartItems as $cartItem) {
                    $book = $cartItem->book;

                    if (!$book) {
                        return $this->errorResponse(
                            404,
                            'Not Found',
                            "Book not found for cart item ID: {$cartItem->id}"
                        );
                    }

                    if (!$book->is_active) {
                        return $this->errorResponse(
                            400,
                            'Bad Request',
                            "Book '{$book->title}' is no longer available"
                        );
                    }

                    if ($book->quantity < $cartItem->quantity) {
                        return $this->errorResponse(
                            400,
                            'Bad Request',
                            "Insufficient stock for book: {$book->title}. Available: {$book->quantity}, Requested: {$cartItem->quantity}"
                        );
                    }

                    $itemTotal = $book->price * $cartItem->quantity;
                    $totalAmount += $itemTotal;

                    $validatedItems[] = $cartItem;
                }

                // Generate order number
                $orderNumber = $this->generateOrderNumber();

                // Tạo order
                $orderData = [
                    'order_number'        => $orderNumber,
                    'total_amount'        => $totalAmount,
                    'note'                => null,
                    'shipping_fee'        => 30000,
                    'shipping_address_id' => $shippingAddress->id,
                    'payment_method_id'   => 1,
                ];

                $order = Order::create($orderData);

                // Tạo order items và cập nhật stock
                foreach ($validatedItems as $cartItem) {
                    $book = $cartItem->book;

                    $order->orderItems()->create([
                        'book_id'  => $cartItem->book_id,
                        'quantity' => $cartItem->quantity,
                        'price'    => $book->price,
                    ]);

                    $book->decrement('quantity', $cartItem->quantity);
                    $book->increment('sold', $cartItem->quantity);

                    // Xóa item khỏi cart sau khi đã tạo order
                    $cartItem->delete();
                }

                // Tạo trạng thái đơn hàng ban đầu
                $this->createInitialOrderStatus($order->id, 'Order placed from web successfully');

                // Cập nhật lại totals của cart
                $this->updateCartTotals($cart->id);

                // Load relationships
                $order->load(['orderItems.book', 'shippingAddress', 'paymentMethod', 'statusHistories.orderStatus']);

                return $this->successResponse(
                    201,
                    'Order created successfully',
                    new OrderResource($order)
                );
            });
        } catch (ValidationException $e) {
            return $this->errorResponse(
                422,
                'Validation Failed',
                $e->errors()
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Error creating order',
                $e->getMessage()
            );
        }
    }

    public function placeOrder(PlaceOrderRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $selectedCartItems = CartItem::where('cart_id', $request->cart_id)
                ->where('is_selected', true)
                ->with('book')
                ->get();

            if ($selectedCartItems->isEmpty()) {
                return $this->errorResponse(
                    400,
                    'Bad Request',
                    'No selected items in the cart to place an order'
                );
            }

            // Kiểm tra stock và tính tổng tiền
            $totalAmount = 0;
            $validatedItems = [];

            foreach ($selectedCartItems as $cartItem) {
                $book = $cartItem->book;

                if (!$book) {
                    return $this->errorResponse(
                        404,
                        'Not Found',
                        "Book not found for cart item ID: {$cartItem->id}"
                    );
                }

                if ($book->quantity < $cartItem->quantity) {
                    return $this->errorResponse(
                        400,
                        'Bad Request',
                        "Insufficient stock for book: {$book->title}. Available: {$book->quantity}, Requested: {$cartItem->quantity}"
                    );
                }

                $itemTotal = $book->price * $cartItem->quantity;
                $totalAmount += $itemTotal;

                $validatedItems[] = $cartItem;
            }

            $orderNumber = $this->generateOrderNumber();

            $orderData = [
                'order_number'        => $orderNumber,
                'total_amount'        => $totalAmount,
                'note'                => $request->note,
                'shipping_fee'        => $request->shipping_fee ?? 0,
                'shipping_address_id' => $request->shipping_address_id,
                'payment_method_id'   => $request->payment_method_id,
            ];

            $order = Order::create($orderData);

            // Tạo order items và cập nhật stock
            foreach ($validatedItems as $cartItem) {
                $book = $cartItem->book;

                $order->orderItems()->create([
                    'book_id'  => $cartItem->book_id,
                    'quantity' => $cartItem->quantity,
                    'price'    => $book->price,
                ]);

                $book->decrement('quantity', $cartItem->quantity);
                $book->increment('sold', $cartItem->quantity);

                $cartItem->delete();
            }

            // Tạo trạng thái đơn hàng ban đầu
            $this->createInitialOrderStatus($order->id, 'Order placed from cart successfully');

            $this->updateCartTotals($request->cart_id);

            $order->load(['orderItems.book', 'shippingAddress', 'paymentMethod', 'statusHistories.orderStatus']);

            return $this->successResponse(
                201,
                'Order placed successfully',
                $order
            );
        });
    }

    /**
     * Create initial order status history
     */
    private function createInitialOrderStatus($orderId, $note = 'Order created')
    {
        $initialStatus = OrderStatus::where('sequence', 1)->first();

        if ($initialStatus) {
            return OrderStatusHistory::create([
                'order_id' => $orderId,
                'order_status_id' => $initialStatus->id,
                'note' => $note
            ]);
        }

        return null;
    }

    /**
     * Update cart totals after removing items
     */
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
