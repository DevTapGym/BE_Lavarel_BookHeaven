<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Exception;

class BookCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books = Book::all();
        $categories = Category::all();

        if ($books->isEmpty() || $categories->isEmpty()) {
            $this->command->warn('No books or categories found. Please run BookSeeder and CategorySeeder first.');
            return;
        }

        $this->command->info('Starting to seed book-category relationships...');

        // Xóa dữ liệu cũ trong bảng book_category
        DB::table('book_category')->truncate();

        $relationshipCount = 0;

        // Tạo mối quan hệ ngẫu nhiên giữa books và categories
        foreach ($books as $book) {
            // Mỗi book sẽ có từ 1-3 categories ngẫu nhiên
            $numCategories = rand(1, min(3, $categories->count()));
            $randomCategories = $categories->random($numCategories);

            foreach ($randomCategories as $category) {
                // Sử dụng attach để thêm mối quan hệ (Laravel sẽ tự động tránh duplicate)
                try {
                    $book->categories()->attach($category->id);
                    $relationshipCount++;
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        $this->command->info('✅ Book-Category relationships seeded successfully!');
        $this->command->info("📚 Total books: {$books->count()}");
        $this->command->info("📂 Total categories: {$categories->count()}");
        $this->command->info("🔗 Total relationships created: {$relationshipCount}");

        // Hiển thị thống kê thêm
        $actualRelationships = DB::table('book_category')->count();
        $this->command->info("✨ Actual relationships in database: {$actualRelationships}");
    }
}
