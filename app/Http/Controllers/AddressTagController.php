<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AddressTag;

class AddressTagController extends Controller
{
    public function index()
    {
        $addressTags = AddressTag::all();
        return $this->successResponse(
            200,
            'AddressTags retrieved successfully',
            $addressTags
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:address_tags,name',
        ]);

        $addressTag = AddressTag::create($validated);

        return $this->successResponse(
            201,
            'AddressTag created successfully',
            $addressTag
        );
    }

    public function update(Request $request)
    {

        $validated = $request->validate([
            'id' => 'required|integer|exists:address_tags,id',
            'name' => 'required|string|max:255|unique:address_tags,name,' . $request->id,
        ]);

        $addressTag = AddressTag::find($validated['id']);
        if (!$addressTag) {
            return $this->errorResponse(
                404,
                'Not Found',
                'AddressTag not found'
            );
        }

        $addressTag->update($validated);

        return $this->successResponse(
            200,
            'AddressTag updated successfully',
            $addressTag
        );
    }

    public function destroy(AddressTag $addressTag)
    {
        if (!$addressTag) {
            return $this->errorResponse(
                404,
                'Not Found',
                'AddressTag not found'
            );
        }

        $addressTag->delete();

        return $this->successResponse(
            200,
            'AddressTag deleted successfully',
            null
        );
    }
}
