<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Book;
use App\Models\User;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Requests\OrderWebRequest;
use App\Http\Requests\ReturnOrderRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use Carbon\Carbon;
use App\Http\Resources\OrderResource;
use App\Models\InventoryHistory;
use App\Models\Promotion;
use App\Http\Resources\OrderListForWebResource;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use App\Models\Customer;
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
        ])
            ->orderBy('created_at', 'desc')
            ->paginate($pageSize);

        $transformed = $paginator->getCollection()->map(function ($order) {
            return [
                'id' => $order->id,
                'code' => $order->order_number,
                'type' => $order->type,
                'totalPrice' => (float) $order->total_amount,
                'receiverEmail' => $order->receiver_email ?? $order->customer->email ?? null,
                'receiverName' => $order->receiver_name,
                'totalPromotionValue' => (float) ($order->total_promotion_value ?? 0),
                'returnFee' => $order->return_fee ? (float) $order->return_fee : null,
                'returnFeeType' => $order->return_fee_type,
                'totalRefundAmount' => $order->total_refund_amount ? (float) $order->total_refund_amount : null,
                'receiverAddress' => $order->receiver_address,
                'receiverPhone' => $order->receiver_phone,
                'paymentMethod' => $order->payment_method ?? null,
                'vnpTxnRef' => $order->vnp_txn_ref,
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
                        'price' => (float) $item->price,
                        'returnQty' => $item->return_qty ?? 0,
                        'book' => [
                            'id' => $item->book->id,
                            'mainText' => $item->book->title,
                            'author' => $item->book->author,
                            'price' => (float) $item->book->price,
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
                    'promotionValue' => (float) $order->promotion->promotion_value,
                    'isMaxPromotionValue' => (bool) $order->promotion->is_max_promotion_value,
                    'maxPromotionValue' => $order->promotion->max_promotion_value ? (float) $order->promotion->max_promotion_value : null,
                    'orderMinValue' => $order->promotion->order_min_value ? (float) $order->promotion->order_min_value : null,
                    'startDate' => $order->promotion->start_date,
                    'endDate' => $order->promotion->end_date,
                    'qtyLimit' => $order->promotion->qty_limit,
                    'isOncePerCustomer' => (bool) $order->promotion->is_once_per_customer,
                    'note' => $order->promotion->note,
                    'isDeleted' => (bool) $order->promotion->is_deleted,
                    'deletedBy' => $order->promotion->deleted_by,
                    'deletedAt' => $order->promotion->deleted_at,
                    'createdAt' => $order->promotion->created_at,
                    'updatedAt' => $order->promotion->updated_at,
                    'createdBy' => $order->promotion->created_by ?? null,
                    'updatedBy' => $order->promotion->updated_by ?? null,
                ] : null,
                'returnOrders' => $order->returnOrders->sortByDesc('created_at')->values()->map(function ($returnOrder) {
                    return [
                        'id' => $returnOrder->id,
                        'code' => $returnOrder->order_number,
                        'type' => $returnOrder->type,
                        'totalPrice' => (float) $returnOrder->total_amount,
                        'returnFee' => $returnOrder->return_fee ? (float) $returnOrder->return_fee : null,
                        'returnFeeType' => $returnOrder->return_fee_type,
                        'returnFeeValue' => $returnOrder->return_fee ? (float) $returnOrder->return_fee : null,
                        'orderItems' => $returnOrder->orderItems->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'quantity' => $item->quantity,
                                'price' => (float) $item->price,
                                'returnQty' => $item->return_qty,
                                'book' => [
                                    'id' => $item->book->id,
                                    'title' => $item->book->title,
                                    'author' => $item->book->author,
                                    'price' => (float) $item->book->price,
                                ],
                            ];
                        })->filter(function ($item) {
                            return $item['quantity'] > 0;
                        }),
                        'totalRefundAmount' => $returnOrder->total_refund_amount ? (float) $returnOrder->total_refund_amount : null,
                        'createdAt' => $returnOrder->created_at,
                    ];
                }),
                'parentOrderId' => $order->parent_id,
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
        $pageSize = $request->query('size', 20);

        $user = Auth::user();

        if (!$user || !$user->customer_id) {
            return $this->errorResponse(
                404,
                'Not Found',
                'User not found or user is not a customer'
            );
        }

        $orders = Order::where('customer_id', $user->customer_id)
            ->where('type', 'SALE')
            ->with([
                'orderItems.book',
                'statusHistories.orderStatus',
                'returnOrders' // Eager load return orders để kiểm tra has_return
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
            $user = Auth::user();
            if (!$user || !$user->customer_id) {
                return $this->errorResponse(
                    404,
                    'Not Found',
                    'User not found or user is not a customer'
                );
            }

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
                    'price' => $itemTotal,
                    'book_id' => $item['book_id']
                ];
            }

            $orderData = [
                'order_number'        => $orderNumber,
                'total_amount'        => $totalAmount,
                'customer_id'           => $user->customer_id,
                'note'                => $request->note,
                'shipping_fee'        => 30000,
                'payment_method'        => $request->payment_method,
                'receiver_name'         => $request->name,
                'receiver_address'      => $request->address,
                'receiver_phone'        => $request->phone,
            ];

            $order = Order::create($orderData);

            // Tạo order items và cập nhật stock
            foreach ($orderItems as $item) {
                $capitalPrice = $item['book']->capital_price ?? 0;
                $order->orderItems()->create([
                    'book_id'  => $item['book_id'],
                    'quantity' => $item['quantity'],
                    'price'    => $item['book']->price,
                    'capital_price' => $capitalPrice,
                    'total_price' => $item['book']->price * $item['quantity'],
                    'total_capital_price' => $capitalPrice * $item['quantity'],
                ]);

                $item['book']->decrement('quantity', $item['quantity']);
                $item['book']->increment('sold', $item['quantity']);
            }

            // Tạo trạng thái đơn hàng ban đầu
            $this->createInitialOrderStatus($order->id, 'Order created successfully');

            $order->load(['orderItems.book', 'statusHistories.orderStatus']);

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


                $capitalPrice = $book->capital_price ?? 0;
                $order->orderItems()->create([
                    'book_id'  => $item['bookId'],
                    'quantity' => $item['quantity'],
                    'price'    => $book->price,
                    'capital_price' => $capitalPrice,
                    'total_price' => $book->price * $item['quantity'],
                    'total_capital_price' => $capitalPrice * $item['quantity'],
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

                    $capitalPrice = $book->capital_price ?? 0;
                    $order->orderItems()->create([
                        'book_id'  => $cartItem->book_id,
                        'quantity' => $cartItem->quantity,
                        'price'    => $book->price,
                        'capital_price' => $capitalPrice,
                        'total_price' => $book->price * $cartItem->quantity,
                        'total_capital_price' => $capitalPrice * $cartItem->quantity,
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
            $user = Auth::user();
            if (!$user || !$user->customer_id) {
                return $this->errorResponse(
                    404,
                    'Not Found',
                    'User not found or user is not a customer'
                );
            }

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

                $validatedItems[] = [
                    'cartItem' => $cartItem,
                    'finalPrice' => $book->price
                ];
            }

            $orderNumber = $this->generateOrderNumber();

            $orderData = [
                'order_number'          => $orderNumber,
                'total_amount'          => $totalAmount,
                'note'                  => $request->note,
                'customer_id'           => $user->customer_id,
                'shipping_fee'          => 30000,
                'payment_method'        => $request->payment_method,
                'receiver_name'         => $request->name,
                'receiver_address'      => $request->address,
                'receiver_phone'        => $request->phone,
            ];

            $order = Order::create($orderData);

            // Tạo order items và cập nhật stock
            foreach ($validatedItems as $item) {
                $cartItem = $item['cartItem'];
                $book = $cartItem->book;

                $capitalPrice = $book->capital_price ?? 0;
                $order->orderItems()->create([
                    'book_id'  => $cartItem->book_id,
                    'quantity' => $cartItem->quantity,
                    'price'    => $book->price, // Sử dụng giá đã tính (có sale_off hoặc giá gốc)
                    'capital_price' => $capitalPrice,
                    'total_price' => $item['finalPrice'] * $cartItem->quantity,
                    'total_capital_price' => $capitalPrice * $cartItem->quantity,
                ]);

                $book->decrement('quantity', $cartItem->quantity);
                $book->increment('sold', $cartItem->quantity);

                $cartItem->delete();
            }

            // Tạo trạng thái đơn hàng ban đầu
            $this->createInitialOrderStatus($order->id, 'Order placed from cart successfully');

            $this->updateCartTotals($request->cart_id);

            $order->load(['orderItems.book', 'statusHistories.orderStatus']);

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

                        InventoryHistory::create([
                            'book_id' => $orderItem->book_id,
                            'order_id' => $order->id,
                            'type' => 'IN',
                            'qty_stock_before' => $orderItem->book->quantity,
                            'qty_change' => (int) $orderItem->quantity,
                            'qty_stock_after' => $orderItem->book->quantity + (int) $orderItem->quantity,
                            'price' => $orderItem->book->price,
                            'total_price' => $orderItem->book->price * $orderItem->quantity,
                            'transaction_date' => now(),
                            'description' => 'Nhập kho do hủy đơn hàng',
                        ]);
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

    public function printOrderView($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return $this->errorResponse(404, 'Not Found', 'Order not found');
        }
        return view('orders.print', [
            'order' => $order,
        ]);
    }

    public function downloadOrderPdf($id)
    {
        $order = Order::with(['orderItems.book'])->find($id);
        if (!$order) {
            return $this->errorResponse(404, 'Not Found', 'Order not found');
        }

        // Generate VietQR image (data URL)
        $finalTotal = (int) (($order->total_amount ?? 0));

        $qrImg = null;
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://api.vietqr.io/v2/generate', [
                'accountNo' => '1031418856',
                'accountName' => 'DANG NGOC TAI',
                'acqId' => '970436',
                'addInfo' => 'Thanh toán đơn hàng ' . ($order->order_number ?? ''),
                'amount' => max(0, $finalTotal),
                'template' => 'compact',
            ]);

            if ($response->ok()) {
                $qrImg = data_get($response->json(), 'data.qrDataURL');
            }
        } catch (\Throwable $e) {
            // swallow QR errors; continue rendering without QR
        }

        $html = view('orders.print_pdf', ['order' => $order, 'qrImg' => $qrImg])->render();

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 226.77, 2000]);
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="order_' . $id . '.pdf"');
    }

    /**
     * Return order (Trả hàng)
     * Converted from Java Spring Boot to Laravel
     */
    public function returnOrder($id, ReturnOrderRequest $request)
    {
        try {
            return DB::transaction(function () use ($id, $request) {
                // Find the original order
                $order = Order::with(['orderItems', 'customer'])->find($id);
                if (!$order) {
                    return $this->errorResponse(404, 'Not Found', 'Order not found');
                }
                // Create return order with initial total_amount = 0
                $returnOrder = new Order();
                $returnOrder->order_number = $this->generateRefundCode($order);
                $returnOrder->type = $request->orderType ?? 'RETURN';
                $returnOrder->customer_id = $request->customerId ?? $order->customer_id;
                $returnOrder->receiver_email = $request->email;
                $returnOrder->receiver_name = $request->receiverName;
                $returnOrder->receiver_address = $request->receiverAddress;
                $returnOrder->receiver_phone = $request->receiverPhone;
                $returnOrder->payment_method = $request->paymentMethod;
                $returnOrder->parent_id = $order->id;
                $returnOrder->total_amount = 0;
                $returnOrder->save();

                $totalPrice = 0;
                $orderItems = [];

                // Process return items and calculate total price
                if ($request->orderItems && is_array($request->orderItems)) {
                    foreach ($request->orderItems as $item) {
                        $book = Book::find($item['bookId']);
                        if (!$book) {
                            return $this->errorResponse(404, 'Not Found', 'Sản phẩm không tồn tại');
                        }

                        if (!isset($item['quantity'])) {
                            continue;
                        }

                        // Calculate price for this item and add to total
                        $itemPrice = $item['quantity'] * $book->price;
                        $totalPrice += $itemPrice;

                        // Create order item for return order
                        $orderItem = new OrderItem();
                        $orderItem->order_id = $returnOrder->id;
                        $orderItem->book_id = $book->id;
                        $orderItem->quantity = $item['quantity'];
                        $orderItem->price = $itemPrice;
                        $orderItem->save();

                        $orderItems[] = $orderItem;


                        if (isset($item['orderItemId'])) {
                            $originOrderItem = OrderItem::find($item['orderItemId']);
                            if ($originOrderItem) {
                                if (($originOrderItem->return_qty + $item['quantity']) > $originOrderItem->quantity) {
                                    return $this->errorResponse(
                                        400,
                                        'Bad Request',
                                        'Return quantity exceeds purchased quantity for book ID: ' . $book->id
                                    );
                                }
                                $originOrderItem->return_qty += $item['quantity'];
                                $originOrderItem->save();
                            }
                        }


                        InventoryHistory::create([
                            'book_id' => $book->id,
                            'order_id' => $order->id,
                            'type' => 'IN',
                            'qty_stock_before' => $book->quantity,
                            'qty_change' => (int) $item['quantity'],
                            'qty_stock_after' => $book->quantity + $item['quantity'],
                            'price' => $book->price,
                            'total_price' => $itemPrice,
                            'transaction_date' => now(),
                            'description' => 'Nhập do trả hàng',
                        ]);

                        // Update book inventory
                        $book->increment('quantity', $item['quantity']);
                        if ($book->sold >= $item['quantity']) {
                            $book->decrement('sold', $item['quantity']);
                        }
                    }
                }

                // Handle promotion refund
                if ($request->totalPromotionValue && $request->promotionId) {
                    $promotion = Promotion::find($request->promotionId);
                    if (!$promotion) {
                        return $this->errorResponse(404, 'Not Found', 'Promotion not found');
                    }

                    // Restore promotion quantity limit
                    if (!is_null($promotion->qty_limit)) {
                        $promotion->increment('qty_limit', 1);
                    }

                    $returnOrder->promotion_id = $promotion->id;
                    $returnOrder->total_promotion_value = $request->totalPromotionValue;
                    $totalPrice -= $request->totalPromotionValue;
                }

                if ($request->returnFee) {
                    $returnOrder->return_fee = $request->returnFee;
                    $returnOrder->return_fee_type = $request->returnFeeType ?? 'value';

                    if ($request->returnFeeType === 'percent') {
                        $totalPrice -= ($totalPrice * ($request->returnFee / 100));
                    } else {
                        $totalPrice -= $request->returnFee;
                    }
                }


                $returnOrder->total_refund_amount = $totalPrice;


                if ($request->statusId) {
                    $this->createReturnOrderStatusWithId($returnOrder->id, $request->statusId, 'Return order created');
                } else {
                    $this->createReturnOrderStatus($returnOrder->id, 'Return order created');
                }

                $returnOrder->total_amount = $totalPrice;
                $returnOrder->save();

                $returnOrder->load(['orderItems.book', 'statusHistories.orderStatus', 'customer']);

                return $this->successResponse(
                    201,
                    'Return order created successfully',
                    $returnOrder
                );
            });
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Error creating return order',
                $e->getMessage()
            );
        }
    }

    /**
     * Generate refund code based on parent order
     * Example: ORDER01012025-TH01, ORDER01012025-TH02
     */
    private function generateRefundCode(Order $order)
    {
        $parentOrderId = $order->id;
        $childOrders = Order::where('parent_id', $parentOrderId)->get();

        $refundCount = $childOrders->count() + 1;

        return $order->order_number . '-TH' . str_pad($refundCount, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Create return order status
     */
    private function createReturnOrderStatus($orderId, $note = 'Return order created')
    {
        // Find the appropriate status for return orders
        // You may need to adjust this based on your OrderStatus table
        $returnStatus = OrderStatus::where('name', 'return')
            ->orWhere('name', 'returned')
            ->orWhere('name', 'refund')
            ->first();

        if (!$returnStatus) {
            // If no specific return status exists, use the first status
            $returnStatus = OrderStatus::first();
        }

        if ($returnStatus) {
            return OrderStatusHistory::create([
                'order_id' => $orderId,
                'order_status_id' => $returnStatus->id,
                'note' => $note
            ]);
        }

        return null;
    }

    /**
     * Create return order status with specific status ID
     */
    private function createReturnOrderStatusWithId($orderId, $statusId, $note = 'Return order created')
    {
        $status = OrderStatus::find($statusId);

        if ($status) {
            return OrderStatusHistory::create([
                'order_id' => $orderId,
                'order_status_id' => $status->id,
                'note' => $note
            ]);
        }

        return null;
    }
}
