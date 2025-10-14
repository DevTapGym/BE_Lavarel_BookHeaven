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
            ['name' => 'Pending', 'description' => 'Order has been placed but not yet processed', 'sequence' => 1],
            ['name' => 'Processing', 'description' => 'Order is being processed', 'sequence' => 2],
            ['name' => 'Shipped', 'description' => 'Order has been shipped', 'sequence' => 3],
            ['name' => 'Delivered', 'description' => 'Order has been delivered to the customer', 'sequence' => 4],
            ['name' => 'Cancelled', 'description' => 'Order has been cancelled', 'sequence' => 5],
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
