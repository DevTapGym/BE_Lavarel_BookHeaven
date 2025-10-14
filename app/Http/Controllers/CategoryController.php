<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Throwable;

class CategoryController extends Controller
{

    public function index()
    {
        try {
            $categories = Category::all();

            $categories->load('books');
            return $this->successResponse(
                200,
                'Categories retrieved successfully',
                [
                    'id' => $categories->id,
                    'name' => $categories->name,
                    'book' => $categories->books,
                ]
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error retrieving categories',
                $th->getMessage()
            );
        }
    }
    public function indexPaginated(Request $request)
    {
        $pageSize = $request->query('size', 10);
        $paginator = Category::paginate($pageSize);
        $data = $this->paginateResponse($paginator);

        return $this->successResponse(
            200,
            'Categories retrieved successfully',
            $data
        );
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
                'description' => 'nullable|string',
            ]);

            $category = Category::create($validated);

            return $this->successResponse(
                201,
                'Category created successfully',
                $category
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error creating category',
                $th->getMessage()
            );
        }
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:categories,id',
                'name' => 'sometimes|string|max:255|unique:categories,name,' . $request->id,
                'description' => 'nullable|string',
            ]);
            $category = Category::findOrFail($validated['id']);
            $category->update($validated);

            return $this->successResponse(
                200,
                'Category updated successfully',
                $category
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error updating category',
                $th->getMessage()
            );
        }
    }

    public function destroy(Category $category)
    {
        try {
            $category->delete();

            return $this->successResponse(
                200,
                'Category deleted successfully',
                null
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error deleting category',
                $th->getMessage()
            );
        }
    }
}
