<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\ShippingAddress;
use App\Http\Resources\ShippingAddressResource;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ShippingAddressController extends Controller
{
    public function getAddressesByCustomer()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse(
                    401,
                    'Unauthorized',
                    'User not authenticated'
                );
            }

            $customerId = $user->customer_id;
            $customer = Customer::with(['shippingAddresses.tag'])->find($customerId);
            if (!$customer) {
                return $this->errorResponse(
                    404,
                    'Not Found',
                    'Customer not found'
                );
            }

            return $this->successResponse(
                200,
                'Shipping addresses retrieved successfully',
                ShippingAddressResource::collection($customer->shippingAddresses)
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error retrieving shipping addresses',
                $th->getMessage()
            );
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'recipient_name' => 'required|string|max:255',
                'address'        => 'required|string|max:500',
                'phone_number'   => 'required|string|max:20',
                'is_default'     => 'boolean',
                'tag_id'         => 'nullable|exists:address_tags,id',
            ]);

            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse(
                    401,
                    'Unauthorized',
                    'User not authenticated'
                );
            }

            $customerId = $user->customer_id;
            $customer = Customer::with(['shippingAddresses.tag'])->find($customerId);
            if (!$customer) {
                return $this->errorResponse(
                    404,
                    'Not Found',
                    'Customer not found'
                );
            }

            $validated['customer_id'] = $customerId;

            // Nếu set default thì bỏ default ở các địa chỉ khác
            if (!empty($validated['is_default']) && $validated['is_default']) {
                ShippingAddress::where('customer_id', $validated['customer_id'])
                    ->update(['is_default' => false]);
            }

            $address = ShippingAddress::create($validated);
            $address->load('tag');

            return $this->successResponse(
                201,
                'Shipping address created successfully',
                new ShippingAddressResource($address)
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error creating shipping address',
                $th->getMessage()
            );
        }
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'             => 'required|exists:shipping_addresses,id',
            'recipient_name' => 'sometimes|string|max:255',
            'address'        => 'sometimes|string|max:500',
            'phone_number'   => 'sometimes|string|max:20',
            'is_default'     => 'boolean',
            'tag_id'         => 'nullable|exists:address_tags,id',
        ]);

        $address = ShippingAddress::find($validated['id']);

        if (!$address) {
            return $this->errorResponse(
                404,
                'Not Found',
                'Shipping address not found'
            );
        }

        if (!empty($validated['is_default']) && $validated['is_default']) {
            ShippingAddress::where('customer_id', $address->customer_id)
                ->update(['is_default' => false]);
        }

        $address->update($validated);

        $address->load('tag');

        return $this->successResponse(
            200,
            'Address address updated successfully',
            new ShippingAddressResource($address)
        );
    }

    public function destroy(ShippingAddress $id)
    {
        try {
            $isDefault = $id->is_default;
            $customerId = $id->customer_id;

            $id->delete();

            if ($isDefault) {
                $remainingAddress = ShippingAddress::where('customer_id', $customerId)
                    ->inRandomOrder()
                    ->first();

                if ($remainingAddress) {
                    $remainingAddress->update(['is_default' => true]);
                }
            }

            return $this->successResponse(
                200,
                'Shipping address deleted successfully',
                null,
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error deleting shipping address',
                $th->getMessage()
            );
        }
    }
}
