<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;

class PermissionController extends Controller
{
    public function indexPaginated(Request $request)
    {
        $pageSize = $request->query('size', 10);
        $paginator = Permission::paginate($pageSize);
        $data = $this->paginateResponse($paginator);

        return $this->successResponse(
            200,
            'Permission retrieved successfully',
            $data
        );
    }

    public function showById($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return $this->errorResponse(
                404,
                'Not Found',
                'Permission not found'
            );
        }

        return $this->successResponse(
            200,
            'Permission retrieved successfully',
            $permission
        );
    }

    public function showByName(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $permission = Permission::where('name', $validated['name'])->first();

        if (!$permission) {
            return $this->errorResponse(
                404,
                'Not Found',
                'Permission not found'
            );
        }

        return $this->successResponse(
            200,
            'Permission retrieved successfully',
            $permission
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name|max:255',
            'apiPath' => 'required|string|max:255',
            'method' => 'required|string|in:GET,POST,PUT,DELETE',
            'module' => 'required|string|max:255',
        ]);

        $validated['guard_name'] = 'api';

        $permission = Permission::create($validated);

        return $this->successResponse(
            201,
            'Permission created successfully',
            $permission
        );
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:permissions,id',
            'name' => 'sometimes|required|string|unique:permissions,name,' . $request->id . '|max:255',
            'apiPath' => 'sometimes|required|string|max:255',
            'method' => 'sometimes|required|string|in:GET,POST,PUT,DELETE',
            'module' => 'sometimes|required|string|max:255',
        ]);

        $permission = Permission::find($validated['id']);
        $permission->update($validated);

        return $this->successResponse(
            200,
            'Permission updated successfully',
            $permission
        );
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return $this->successResponse(
            200,
            'Permission deleted successfully',
            null
        );
    }
}
