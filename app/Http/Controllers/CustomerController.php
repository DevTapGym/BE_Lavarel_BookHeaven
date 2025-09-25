<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class CustomerController extends Controller
{
    public function indexPaginated(Request $request)
    {
        $pageSize = $request->query('size', 10);
        $paginator = Customer::paginate($pageSize);

        $data = $this->paginateResponse($paginator);

        return $this->successResponse(200, 'Customers retrieved successfully', $data);
    }

    public function show(Customer $customer)
    {
        return $this->successResponse(
            200,
            'Customer retrieved successfully',
            $customer
        );
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'  => 'sometimes|required|string|max:255',
                'email' => 'required|email|unique:customers,email',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'gender' => 'nullable|in:male,female,other',
                'date_of_birth' => 'nullable|date',
            ]);

            $customer = Customer::create($validated);
            return $this->successResponse(
                201,
                'Customer created successfully',
                $customer
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error creating customer',
                $th->getMessage(),
            );
        }
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'    => 'required|exists:customers,id',
                'name'  => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:customers,email,' . $request->id,
                'phone' => 'nullable|string|max:10',
                'address' => 'nullable|string|max:255',
                'gender' => 'nullable|in:male,female,other',
                'date_of_birth' => 'nullable|date',
            ]);

            $customer = Customer::findOrFail($validated['id']);
            $customer->update($validated);

            return $this->successResponse(
                200,
                'Update customer successfully',
                $customer
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                404,
                'Not Found',
                'Customer not found'
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error updating customer',
                $th->getMessage()
            );
        }
    }


    public function destroy(Customer $customer)
    {
        try {
            $customer->delete();
            return $this->successResponse(
                200,
                'Customer deleted successfully',
                null
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error deleting customer',
                $th->getMessage()
            );
        }
    }
}
