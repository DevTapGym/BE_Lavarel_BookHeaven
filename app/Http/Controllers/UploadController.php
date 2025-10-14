<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Book;
use App\Models\BookImage;
use App\Models\PaymentMethod;
use App\Models\User;

class UploadController extends Controller
{
    public function uploadAvatar(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
                'user_id' => 'required|integer|exists:books,id',
            ]);

            // Kiểm tra sách có tồn tại không
            $user = User::findOrFail($request->user_id);

            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();

            $fileName = 'Avatar_' . $user->id . '_' . now()->format('Ymd_His') . '.' . $extension;

            $path = $file->storeAs('Avatar', $fileName, 'public');
            $url = Storage::url($path);

            // Cập nhật thumbnail cho sách
            $user->update([
                'avatar' => $url
            ]);

            return $this->successResponse(
                200,
                'Upload avatar successful',
                $user->fresh(),
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                404,
                'Not Found',
                'User does not exist.'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                $e->getMessage(),
            );
        }
    }

    public function uploadThumbnailBook(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
                'book_id' => 'required|integer|exists:books,id',
            ]);

            // Kiểm tra sách có tồn tại không
            $book = Book::findOrFail($request->book_id);

            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();

            $fileName = 'Thumbnail_' . $book->id . '_' . now()->format('Ymd_His') . '.' . $extension;

            $path = $file->storeAs('BookImages', $fileName, 'public');
            $url = Storage::url($path);

            // Cập nhật thumbnail cho sách
            $book->update([
                'thumbnail' => $url
            ]);

            return $this->successResponse(
                200,
                'Upload image successful and book thumbnail updated',
                $book->fresh(),
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                404,
                'Book not found',
                'The specified book does not exist.'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                $e->getMessage(),
            );
        }
    }

    public function uploadImageBook(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
                'book_id' => 'required|integer|exists:books,id',
            ]);

            // Kiểm tra sách có tồn tại không
            $book = Book::findOrFail($request->book_id);

            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();

            $fileName = 'BookImage_' . $book->id . '_' . now()->format('Ymd_His') . '_' . uniqid() . '.' . $extension;

            $path = $file->storeAs('BookImages', $fileName, 'public');
            $url = Storage::url($path);

            // Thêm ảnh vào bảng book_images
            $bookImage = BookImage::create([
                'book_id' => $book->id,
                'url' => $url
            ]);

            return $this->successResponse(
                200,
                'Upload book image successful',
                [
                    'book_id' => $book->id,
                    'image_id' => $bookImage->id,
                    'path' => $path,
                    'url' => $url,
                    'book_images_count' => $book->bookImages()->count()
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                404,
                'Book not found',
                'The specified book does not exist.'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                $e->getMessage(),
            );
        }
    }

    public function uploadLogoPaymentMethod(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
                'payment_id' => 'required|integer|exists:payment_methods,id',
            ]);

            // Kiểm tra sách có tồn tại không
            $payment = PaymentMethod::findOrFail($request->payment_id);

            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();

            $fileName = 'Logo_Payment_' . $payment->id . '_' . now()->format('Ymd_His') . '.' . $extension;

            $path = $file->storeAs('Logo', $fileName, 'public');
            $url = Storage::url($path);

            // Cập nhật thumbnail cho sách
            $payment->update([
                'logo_url' => $url
            ]);

            return $this->successResponse(
                200,
                'Upload logo payment method successful',
                $payment->fresh(),
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                404,
                'Not Found',
                'Payment method does not exist'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                $e->getMessage(),
            );
        }
    }

    public function uploadPdf(Request $request)
    {
        try {
            $request->validate([
                'pdf' => 'required|file|mimes:pdf|max:10240',
            ]);

            $file = $request->file('pdf');

            $extension = $file->getClientOriginalExtension();
            $fileName = 'Document_' . now()->format('Ymd_His') . '.' . $extension;
            $path = $file->storeAs('pdfs', $fileName, 'local');

            return $this->successResponse(
                200,
                'Upload PDF successful',
                ['path' => $path]
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                $e->getMessage(),
            );
        }
    }

    public function downloadPdf($fileName)
    {
        try {
            $path = 'pdfs/' . $fileName;

            if (!Storage::disk('local')->exists($path)) {
                return $this->errorResponse(
                    404,
                    'Not Found',
                    'File Not Found'
                );
            }

            $absolutePath = Storage::disk('local')->path($path);
            return response()->download($absolutePath);
        } catch (Exception $e) {
            return $this->errorResponse(
                500,
                'Internal Server Error',
                $e->getMessage(),
            );
        }
    }
}
