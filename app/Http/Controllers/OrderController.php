<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Book;
use App\Models\User;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Requests\OrderWebRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use Carbon\Carbon;
use App\Http\Resources\OrderResource;
use App\Models\InventoryHistory;
use App\Models\Promotion;
use App\Models\ShippingAddress;
use App\Http\Resources\OrderForWebResource;
use App\Http\Resources\OrderListForWebResource;
use App\Models\Customer;
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
        $paginator = Order::with([
            'orderItems.book.bookImages',
            'statusHistories.orderStatus',
            'promotion',
            'customer'
        ])->paginate($pageSize);

        $transformed = $paginator->getCollection()->map(function ($order) {
            return [
                'id' => $order->id,
                'code' => $order->order_number,
                'type' => null, // not present in schema, set null or derive if needed
                'totalPrice' => $order->total_amount,
                'receiverEmail' => $order->customer->email ?? null,
                'receiverName' => $order->receiver_name,
                'totalPromotionValue' => $order->total_promotion_value ?? 0,
                'returnFee' => null,
                'returnFeeType' => null,
                'totalRefundAmount' => null,
                'receiverAddress' => $order->receiver_address,
                'receiverPhone' => $order->receiver_phone,
                'paymentMethod' => $order->payment_method ?? null,
                'vnpTxnRef' => null,
                'createdBy' => $order->created_by ?? null,
                'updatedBy' => $order->updated_by ?? null,
                'createdAt' => $order->created_at,
                'updatedAt' => $order->updated_at,
                'customer' => [
                    'id' => $order->customer->id ?? null,
                    'name' => $order->customer->name ?? null,
                    'phone' => $order->customer->phone ?? null,
                    'email' => $order->customer->email ?? null,
                ],
                'orderItems' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'book' => [
                            'id' => $item->book->id,
                            'mainText' => $item->book->title,
                            'author' => $item->book->author,
                            'price' => $item->book->price,
                        ],
                    ];
                }),
                'orderShippingEvents' => $order->statusHistories->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'createdAt' => $history->created_at,
                        'shippingStatus' => [
                            'id' => $history->orderStatus->id ?? null,
                            'status' => $history->orderStatus->name ?? null,
                            'label' => $history->orderStatus->description ?? null,
                        ],
                        'note' => $history->note,
                    ];
                }),
                'promotion' => $order->promotion ? [
                    'id' => $order->promotion->id,
                    'code' => $order->promotion->code,
                    'name' => $order->promotion->name,
                    'status' => $order->promotion->status,
                    'promotionType' => $order->promotion->promotion_type,
                    'promotionValue' => $order->promotion->promotion_value,
                    'isMaxPromotionValue' => $order->promotion->is_max_promotion_value,
                    'maxPromotionValue' => $order->promotion->max_promotion_value,
                    'orderMinValue' => $order->promotion->order_min_value,
                    'startDate' => $order->promotion->start_date,
                    'endDate' => $order->promotion->end_date,
                    'qtyLimit' => $order->promotion->qty_limit,
                    'isOncePerCustomer' => $order->promotion->is_once_per_customer,
                    'note' => $order->promotion->note,
                    'isDeleted' => $order->promotion->is_deleted,
                    'deletedBy' => $order->promotion->deleted_by,
                    'deletedAt' => $order->promotion->deleted_at,
                    'createdAt' => $order->promotion->created_at,
                    'updatedAt' => $order->promotion->updated_at,
                    'createdBy' => $order->promotion->created_by ?? null,
                    'updatedBy' => $order->promotion->updated_by ?? null,
                ] : null,
                'returnOrders' => [],
                'parentOrderId' => null,
            ];
        });

        $paginator->setCollection($transformed);

        $data = $this->paginateResponse($paginator);

        return $this->successResponse(
            200,
            'Order retrieved successfully',
            $data
        );
    }

    public function show(Order $order)
    {
        $order->load([
            'orderItems.book',
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

        $orders = Order::where('customer_id', $user->customer_id)
            ->with([
                'orderItems.book',
                'statusHistories.orderStatus'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($pageSize);

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
            $orders = Order::where('customer_id', $user->customer_id)
                ->with([
                    'orderItems.book.bookImages',
                    'statusHistories.orderStatus'
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse(
                200,
                'User orders retrieved successfully',
                OrderListForWebResource::collection($orders)
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

    public function createOrderFromWebPayload(OrderWebRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $orderNumber = $this->generateOrderNumber();
            $customer = Customer::find($request->customerId);
            if (!$customer) {
                return $this->errorResponse(404, 'Not Found', 'Customer not found');
            }

            $order = Order::create([
                'order_number'        => $orderNumber,
                'total_amount'        => 0,
                'note'                => null,
                'shipping_fee'        => 0,
                'payment_method'      => $request->paymentMethod,
                'customer_id'         => $request->customerId,
            ]);

            $totalAmount = 0;

            foreach ($request->orderItems as $item) {
                $book = Book::find($item['bookId']);

                if (!$book) {
                    return $this->errorResponse(
                        404,
                        'Not Found',
                        "Book with ID {$item['bookId']} not found"
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


                $order->orderItems()->create([
                    'book_id'  => $item['bookId'],
                    'quantity' => $item['quantity'],
                    'price'    => $book->price,
                ]);

                InventoryHistory::create([
                    'book_id'          => $book->id,
                    'order_id'         => $order->id,
                    'type'             => 'OUT',
                    'qty_stock_before' => $book->quantity,
                    'qty_change'       => (int) $item['quantity'],
                    'qty_stock_after'  => $book->quantity - (int) $item['quantity'],
                    'price'            => $book->price,
                    'total_price'      => $itemTotal,
                    'transaction_date' => now(),
                    'description'      => 'Xuất kho do bán hàng',
                ]);

                $book->decrement('quantity', $item['quantity']);
                $book->increment('sold', $item['quantity']);
            }

            $totalPromotionValue = 0;
            if ($request->promotionId) {
                $promotion = Promotion::find($request->promotionId);

                if (!$promotion) {
                    return $this->errorResponse(404, 'Not Found', 'Promotion not found');
                }

                if ($promotion->status == '0') {
                    return $this->errorResponse(400, 'Bad Request', 'Mã khuyến mãi không còn hiệu lực');
                }

                if (!is_null($promotion->order_min_value) && $totalAmount < (float) $promotion->order_min_value) {
                    return $this->errorResponse(400, 'Bad Request', 'Đơn hàng chưa đạt giá trị tối thiểu để sử dụng mã khuyến mãi');
                }

                $now = now();
                if (($promotion->start_date && $now->lt($promotion->start_date)) || ($promotion->end_date && $now->gt($promotion->end_date))) {
                    return $this->errorResponse(400, 'Bad Request', 'Mã khuyến mãi không còn hiệu lực');
                }


                if (!is_null($promotion->qty_limit) && (int) $promotion->qty_limit <= 0) {
                    return $this->errorResponse(400, 'Bad Request', 'Mã khuyến mãi đã hết lượt sử dụng');
                }

                if ($promotion->is_once_per_customer && $request->customerId) {
                    $usedBefore = Order::where('promotion_id', $promotion->id)
                        ->where('customer_id', $request->customerId)
                        ->exists();
                    if ($usedBefore) {
                        return $this->errorResponse(400, 'Bad Request', 'Khách hàng đã sử dụng mã khuyến mãi này');
                    }
                }

                if ($promotion->promotion_type === 'percent') {
                    $totalPromotionValue = $totalAmount * ((float) $promotion->promotion_value / 100);
                    if ($promotion->is_max_promotion_value && !is_null($promotion->max_promotion_value)) {
                        $totalPromotionValue = min($totalPromotionValue, (float) $promotion->max_promotion_value);
                    }
                } else {
                    $totalPromotionValue = (float) $promotion->promotion_value;
                }

                $totalPromotionValue = max(0, min($totalPromotionValue, $totalAmount));
                if (!is_null($promotion->qty_limit)) {
                    $promotion->decrement('qty_limit');
                }


                $order->promotion_id = $promotion->id;
                $order->total_promotion_value = $totalPromotionValue;

                // Reduce total by discount
                $totalAmount -= $totalPromotionValue;
            }

           

            $order->update([
                'total_amount' => $totalAmount,
                'promotion_id' => $order->promotion_id ?? null,
                'total_promotion_value' => $order->total_promotion_value ?? 0,
                'customer_id' => $request->customerId,
                'receiver_name' => $request->receiverName ? $request->receiverName : $customer->name,
                'receiver_address' => $request->receiverAddress ? $request->receiverAddress : $customer->address,
                'receiver_phone' => $request->receiverPhone ? $request->receiverPhone : $customer->phone,
            ]);


            $this->createCompletedOrderStatus($order->id, 'Order created successfully');

            $order->load(['orderItems.book', 'statusHistories.orderStatus']);

            return $this->successResponse(
                201,
                'Order created successfully',
                $order
            );
        });
    }


    public function placeOrderForWeb(Request $request)
    {
        try {
            $validated = $request->validate([
                'accountId'     => 'required|integer',
                'address'       => 'required|string|max:500',
                'email'         => 'required|email',
                'name'          => 'required|string|max:255',
                'paymentMethod' => 'required|string|in:cod,banking',
                'phone'         => 'required|string|max:20',
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
                    'customer_id'         => $customerId,
                    'order_number'        => $orderNumber,
                    'total_amount'        => $totalAmount,
                    'note'                => null,
                    'shipping_fee'        => 30000,
                    'payment_method'     => $validated['paymentMethod'],
                    'receiver_name'      => $validated['name'],
                    'receiver_address'    => $validated['address'],
                    'receiver_phone'      => $validated['phone'],
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

                // Load relationships (không có shippingAddress vì lưu trực tiếp receiver_name và receiver_address)
                $order->load(['orderItems.book', 'statusHistories.orderStatus']);

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

    public function updateOrder(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $order = Order::find($request->id);
            if (!$order) {
                return $this->errorResponse(404, 'Not Found', 'Order not found');
            }
            $validated = $request->validate([
                'statusId' => 'required|exists:order_statuses,id',
                'note' => 'nullable|string|max:500',
            ]);

			// Restock books if order is canceled
			$newStatus = OrderStatus::find($validated['statusId']);
			if ($newStatus && $newStatus->name === 'canceled') {
				$order->loadMissing('orderItems.book');
				foreach ($order->orderItems as $orderItem) {
					if ($orderItem->book) {
						$orderItem->book->increment('quantity', (int) $orderItem->quantity);
						$currentSold = (int) ($orderItem->book->sold ?? 0);
						$newSold = max(0, $currentSold - (int) $orderItem->quantity);
						$orderItem->book->update(['sold' => $newSold]);
					}
				}
			}
            $order->statusHistories()->create([
                'order_status_id' => $validated['statusId'],
				'note' => $validated['note'] ?? null,
            ]);
            $order->update([
                'status_id' => $validated['statusId'],
            ]);
            return $this->successResponse(200, 'Order updated successfully', $order);
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

    private function createCompletedOrderStatus($orderId, $note = 'Order completed')
    {
        $completedStatus = OrderStatus::where('name', 'completed')->first();

        if ($completedStatus) {
            return OrderStatusHistory::create([
                'order_id' => $orderId,
                'order_status_id' => $completedStatus->id,
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
