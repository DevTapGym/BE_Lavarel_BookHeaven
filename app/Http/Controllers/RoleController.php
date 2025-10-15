<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\RoleRequest;
use Illuminate\Validation\Rule;
use Exception;

class RoleController extends Controller
{
    public function getAllRoles()
    {
        $roles = Role::with('permissions')->get();

        $formattedRoles = $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'createdAt' => $role->created_at,
                'updatedAt' => $role->updated_at,
                'createdBy' => $role->createdBy ?? null,
                'updatedBy' => $role->updatedBy ?? null,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'apiPath' => $permission->apiPath ?? null,
                        'method' => $permission->method ?? null,
                        'module' => $permission->module ?? null,
                        'createdAt' => $permission->created_at,
                        'updatedAt' => $permission->updated_at,
                        'createdBy' => $permission->createdBy ?? null,
                        'updatedBy' => $permission->updatedBy ?? null,
                    ];
                })
            ];
        });

        return $this->successResponse(
            200,
            'All roles retrieved successfully',
            $formattedRoles
        );
    }

    public function show($id)
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return $this->errorResponse(
                404,
                'Not Found',
                'Role not found'
            );
        }

        $formattedRole = [
            'id' => $role->id,
            'name' => $role->name,
            'createdAt' => $role->created_at,
            'updatedAt' => $role->updated_at,
            'createdBy' => $role->createdBy ?? null,
            'updatedBy' => $role->updatedBy ?? null,
            'permissions_count' => $role->permissions->count(),
            'permissions' => $role->permissions->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'apiPath' => $permission->apiPath ?? null,
                    'method' => $permission->method ?? null,
                    'module' => $permission->module ?? null,
                    'createdAt' => $permission->created_at,
                    'updatedAt' => $permission->updated_at,
                    'createdBy' => $permission->createdBy ?? null,
                    'updatedBy' => $permission->updatedBy ?? null,
                ];
            })
        ];

        return $this->successResponse(
            200,
            'Role retrieved successfully',
            $formattedRole
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name|max:255',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        return DB::transaction(function () use ($validated) {
            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'api'
            ]);

            if (isset($validated['permissions']) && !empty($validated['permissions'])) {
                $permissions = Permission::whereIn('id', $validated['permissions'])->get();
                $role->syncPermissions($permissions);
            }

            $role->load('permissions');

            $formattedRole = [
                'id' => $role->id,
                'name' => $role->name,
                'createdAt' => $role->created_at,
                'updatedAt' => $role->updated_at,
                'createdBy' => $role->createdBy ?? null,
                'updatedBy' => $role->updatedBy ?? null,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'apiPath' => $permission->apiPath ?? null,
                        'method' => $permission->method ?? null,
                        'module' => $permission->module ?? null,
                        'createdAt' => $permission->created_at,
                        'updatedAt' => $permission->updated_at,
                        'createdBy' => $permission->createdBy ?? null,
                        'updatedBy' => $permission->updatedBy ?? null,
                    ];
                })
            ];

            return $this->successResponse(
                201,
                'Role created successfully',
                $formattedRole
            );
        });
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:roles,id',
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role = Role::find($validated['id']);

        if (!$role) {
            return $this->errorResponse(
                404,
                'Not Found',
                'Role not found'
            );
        }

        // Cập nhật validation cho name với ignore hiện tại role
        if (isset($validated['name'])) {
            $nameValidation = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('roles', 'name')->ignore($role->id)
                ]
            ]);
            $validated['name'] = $nameValidation['name'];
        }

        return DB::transaction(function () use ($role, $validated) {
            if (isset($validated['name'])) {
                $role->update(['name' => $validated['name']]);
            }

            if (isset($validated['permissions'])) {
                if (empty($validated['permissions'])) {
                    $role->syncPermissions([]);
                } else {
                    $permissions = Permission::whereIn('id', $validated['permissions'])->get();
                    $role->syncPermissions($permissions);
                }
            }

            $role->load('permissions');

            $formattedRole = [
                'id' => $role->id,
                'name' => $role->name,
                'createdAt' => $role->created_at,
                'updatedAt' => $role->updated_at,
                'createdBy' => $role->createdBy ?? null,
                'updatedBy' => $role->updatedBy ?? null,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'apiPath' => $permission->apiPath ?? null,
                        'method' => $permission->method ?? null,
                        'module' => $permission->module ?? null,
                        'createdAt' => $permission->created_at,
                        'updatedAt' => $permission->updated_at,
                        'createdBy' => $permission->createdBy ?? null,
                        'updatedBy' => $permission->updatedBy ?? null,
                    ];
                })
            ];

            return $this->successResponse(
                200,
                'Role updated successfully',
                $formattedRole
            );
        });
    }

    public function destroy($id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return $this->errorResponse(
                    404,
                    'Not Found',
                    'Role not found'
                );
            }

            $usersCount = $role->users()->count();
            if ($usersCount > 0) {
                return $this->errorResponse(
                    400,
                    'Bad Request',
                    "Cannot delete role. It is assigned to {$usersCount} user(s). Please remove role from users first."
                );
            }

            return DB::transaction(function () use ($role) {
                $roleName = $role->name;
                $role->syncPermissions([]);
                $role->delete();

                return $this->successResponse(
                    200,
                    'Role deleted successfully',
                    [
                        'deleted_role_name' => $roleName,
                        'deleted_at' => now()->toISOString()
                    ]
                );
            });
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                'Failed to delete role: ' . $e->getMessage()
            );
        }
    }
}
