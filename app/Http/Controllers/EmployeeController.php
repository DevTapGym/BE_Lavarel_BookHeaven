<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Throwable;

class EmployeeController extends Controller
{
    public function show(Employee $employee)
    {
        return $this->successResponse(
            200,
            'Employee retrieved successfully',
            $employee
        );
    }

    public function indexPaginated(Request $request)
    {
        $pageSize = $request->query('size', 10);
        $paginator = Employee::paginate($pageSize);

        $data = $this->paginateResponse($paginator);

        return $this->successResponse(200, 'Customers retrieved successfully', $data);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'  => 'required|string|max:255',
                'email' => 'required|email|unique:employees,email',
                'phone' => 'sometimes|required|string|max:20',
                'address' => 'nullable|string|max:255',
                'position' => 'sometimes|required|string|max:100',
                'date_of_birth' => 'nullable|date',
                'salary' => 'sometimes|required|numeric',
                'hire_date' => 'sometimes|required|date',
            ]);

            $employee = Employee::create($validated);
            return $this->successResponse(
                201,
                'Cretate employee successfully',
                $employee
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error creating employee',
                $th->getMessage(),
            );
        }
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:employees,id',
                'name'  => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:employees,email,' . $request->id,
                'phone' => 'sometimes|required|string|max:20',
                'address' => 'nullable|string|max:255',
                'position' => 'sometimes|required|string|max:100',
                'date_of_birth' => 'nullable|date',
                'salary' => 'sometimes|required|numeric',
                'hire_date' => 'sometimes|required|date',
            ]);
            $employee = Employee::findOrFail($validated['id']);
            $employee->update($validated);

            return $this->successResponse(
                200,
                'Update employee successfully',
                $employee
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error updating employee',
                $th->getMessage(),
            );
        }
    }

    public function destroy(Employee $employee)
    {
        try {
            $employee->delete();
            return $this->successResponse(
                200,
                'Employee deleted successfully',
                null
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error deleting employee',
                $th->getMessage()
            );
        }
    }
}
