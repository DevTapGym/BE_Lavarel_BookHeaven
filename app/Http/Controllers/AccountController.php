<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserAccountResource;
use App\Http\Resources\UserAccountListResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function indexPaginated(Request $request)
    {
        $pageSize = $request->query('size', 10);

        $paginator = User::with(['roles', 'customer', 'employee'])
            ->paginate($pageSize);

        $paginator->setCollection(
            collect(UserAccountListResource::collection($paginator->items()))
        );

        $data = $this->paginateResponse($paginator);

        return $this->successResponse(
            200,
            'Account retrieved successfully',
            $data
        );
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
