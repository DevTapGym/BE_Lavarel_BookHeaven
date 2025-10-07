<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\ShippingAddress;
use App\Http\Resources\ShippingAddressResource;

class ShippingAddressController extends Controller
{
    public function getAddressesByCustomer($id)
    {
        $customer = Customer::with(['shippingAddresses.tag'])->find($id);

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
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'address'        => 'required|string|max:500',
            'phone_number'   => 'required|string|max:20',
            'is_default'     => 'boolean',
            'customer_id'    => 'required|exists:customers,id',
            'tag_id'         => 'nullable|exists:address_tags,id',
        ]);

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
        $id->delete();

        return $this->successResponse(
            200,
            'Shipping address deleted successfully',
            null,
        );
    }
}
