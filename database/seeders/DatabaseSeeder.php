<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            PromotionSeeder::class,
            // UserSeeder::class,
            // CategorySeeder::class,
            // BookSeeder::class,
            // SupplierSeeder::class,
            // AddressTagSeeder::class,
            // PaymentSeeder::class,
            // OrderStatusSeeder::class,
            // BookCategorySeeder::class,
        ]);
    }
}
