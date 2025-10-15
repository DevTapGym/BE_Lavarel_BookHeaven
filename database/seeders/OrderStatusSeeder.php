<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\OrderStatus;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        // Add default order statuses
        $statuses = [
            ['name' => 'wait_confirm', 'description' => 'Chờ xác nhận', 'sequence' => 1],
            ['name' => 'processing', 'description' => 'Đang xử lý', 'sequence' => 2],
            ['name' => 'shipping', 'description' => 'Đang giao hàng', 'sequence' => 3],
            ['name' => 'payment_completed', 'description' => 'Đã thanh toán', 'sequence' => 4],
            ['name' => 'canceled', 'description' => 'Đã hủy', 'sequence' => 5],
            ['name' => 'returned', 'description' => 'Đã hoàn trả', 'sequence' => 6],
            ['name' => 'completed', 'description' => 'Đã hoàn thành', 'sequence' => 7],

        ];

        foreach ($statuses as $status) {
            OrderStatus::updateOrCreate(
                ['name' => $status['name']],
                ['description' => $status['description'], 'sequence' => $status['sequence']]
            );
        }

        $this->command->info('Order statuses have been seeded successfully!');
    }
}
