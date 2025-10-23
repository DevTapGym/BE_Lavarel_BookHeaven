<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserAccountResource;
use App\Http\Resources\UserAccountListResource;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Models\Customer;
use Throwable;

class AccountController extends Controller
{
    public function indexPaginated(Request $request)
    {
        try {
            $pageSize = $request->query('size', 10);

            // Dùng QueryBuilder để build query động
            $paginator = QueryBuilder::for(User::class)
                ->with(['roles', 'customer', 'employee'])
                ->allowedFilters([
                    // Lọc theo cột trong bảng users
                    AllowedFilter::partial('name'),
                    AllowedFilter::partial('email'),

                    // Lọc theo số điện thoại của customer hoặc employee
                    AllowedFilter::callback('phone', function ($query, $value) {
                        $query->whereHas('customer', function ($q) use ($value) {
                            $q->where('phone', 'like', "%{$value}%");
                        })->orWhereHas('employee', function ($q) use ($value) {
                            $q->where('phone', 'like', "%{$value}%");
                        });
                    }),

                    // Lọc theo tên vai trò (role name)
                    AllowedFilter::callback('role', function ($query, $value) {
                        // $value có thể là chuỗi hoặc mảng. Ví dụ: ?filter[role][]=ADMIN&filter[role][]=EMPLOYEE
                        $roles = is_array($value) ? $value : explode(',', $value);

                        $query->whereHas('roles', function ($q) use ($roles) {
                            $q->whereIn('name', $roles);
                        });
                    }),
                ])
                ->allowedSorts([
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',

                    // Sắp theo số điện thoại (customer trước, employee sau)
                    AllowedSort::custom('phone', new class implements \Spatie\QueryBuilder\Sorts\Sort {
                        public function __invoke($query, $descending, string $property)
                        {
                            $direction = $descending ? 'desc' : 'asc';
                            $query->leftJoin('customers', 'users.customer_id', '=', 'customers.id')
                                ->orderBy('customers.phone', $direction);
                        }
                    }),
                ])
                ->paginate($pageSize)
                ->appends(request()->query());

            $paginator->setCollection(
                collect(UserAccountListResource::collection($paginator->items()))
            );

            $data = $this->paginateResponse($paginator);

            return $this->successResponse(
                200,
                'Accounts retrieved successfully',
                $data
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error retrieving accounts',
                $th->getMessage()
            );
        }
    }

    public function show(User $user)
    {
        // Load relationships
        $user->load(['roles', 'customer', 'employee']);

        return $this->successResponse(
            200,
            'Account retrieved successfully',
            new UserAccountResource($user)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:6|max:255',
            'role' => 'required|int|exists:roles,id',
            'customer_id' => 'sometimes|nullable|integer|exists:customers,id',
            'employee_id' => 'sometimes|nullable|integer|exists:employees,id',
        ]);

        return DB::transaction(function () use ($validated) {
            $role = $validated['role'];

            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
                'customer_id' => $validated['customer_id'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
            ];

            $user = User::create($userData);

            if (!empty($validated['customer_id'])) {
                $customer = Customer::find($validated['customer_id']);
                if ($customer) {
                    $customer->update(['email' => $validated['email']]);
                }
            }

            if (!empty($validated['employee_id'])) {
                $employee = Employee::find($validated['employee_id']);
                if ($employee) {
                    $employee->update(['email' => $validated['email']]);
                }
            }

            $user->assignRole($role);
            $user->load(['roles', 'customer', 'employee']);

            return $this->successResponse(
                201,
                'Account created successfully',
                new UserAccountResource($user)
            );
        });
    }


    public function toggleActiveStatus(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();

        return $this->successResponse(
            200,
            'Account status updated successfully',
            new UserAccountResource($user)
        );
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'role' => 'sometimes|int|exists:roles,id',
            'is_active' => 'sometimes|boolean',
            'customer_id' => 'sometimes|nullable|integer|exists:customers,id',
            'employee_id' => 'sometimes|nullable|integer|exists:employees,id',
        ]);

        $user = User::find($validated['id']);

        if (!$user) {
            return $this->errorResponse(
                404,
                'Not Found',
                'User not found'
            );
        }


        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['is_active'])) {
            $user->is_active = $validated['is_active'];
        }
        if (isset($validated['customer_id'])) {
            $user->customer_id = $validated['customer_id'];
        }
        if (isset($validated['employee_id'])) {
            $user->employee_id = $validated['employee_id'];
        }
        $user->save();

        // Sync role if provided
        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        // Reload relationships
        $user->load(['roles', 'customer', 'employee']);

        return $this->successResponse(
            200,
            'Account updated successfully',
            new UserAccountResource($user)
        );
    }

    public function destroy(User $user)
    {
        $user->delete();

        return $this->successResponse(
            200,
            'Account deleted successfully'
        );
    }
}
