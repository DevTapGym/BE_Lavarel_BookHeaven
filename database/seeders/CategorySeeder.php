<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Fiction',
            'Science',
            'History',
            'Technology',
            'Art',
            'Business',
            'Children'
        ];

        foreach ($categories as $name) {
            Category::create([
                'name' => $name,
                'description' => "Description for {$name}"
            ]);
        }
    }
}
