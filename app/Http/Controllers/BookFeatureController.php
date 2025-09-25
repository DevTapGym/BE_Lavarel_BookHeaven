<?php

namespace App\Http\Controllers;

use App\Models\BookFeature;
use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookFeatureController extends Controller
{
    public function index($book_id)
    {
        $book = Book::with('bookFeatures')->findOrFail($book_id);

        return $this->successResponse(
            200,
            'Book features retrieved successfully',
            $book->bookFeatures
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_id'        => 'required|exists:books,id',
            'features'       => 'required|array',
            'features.*'     => 'required|string|max:255',
        ]);

        try {
            $book = Book::findOrFail($validated['book_id']);

            $createdFeatures = [];
            DB::transaction(function () use ($book, $validated, &$createdFeatures) {
                foreach ($validated['features'] as $name) {
                    $createdFeatures[] = $book->bookFeatures()->create(['feature_name' => $name]);
                }
            });

            return $this->successResponse(
                201,
                'Features added successfully',
                $createdFeatures
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error adding features',
                $th->getMessage()
            );
        }
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'feature_id'    => 'required|exists:book_features,id',
            'feature_name' => 'required|string|max:255',
        ]);

        try {

            $feature = BookFeature::findOrFail($validated['feature_id']);
            $feature->update($validated);

            return $this->successResponse(
                200,
                'Feature updated successfully',
                $feature
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error updating feature',
                $th->getMessage()
            );
        }
    }

    public function destroy($feature_id)
    {
        try {
            $feature = BookFeature::findOrFail($feature_id);
            $feature->delete();

            return $this->successResponse(
                200,
                'Feature deleted successfully',
                null
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error deleting feature',
                $th->getMessage()
            );
        }
    }

    public function destroyAll($book_id)
    {
        try {
            $book = Book::findOrFail($book_id);
            $book->bookFeatures()->delete();

            return $this->successResponse(
                200,
                'All features deleted successfully',
                null
            );
        } catch (Throwable $th) {
            return $this->errorResponse(
                500,
                'Error deleting all features',
                $th->getMessage()
            );
        }
    }
}
