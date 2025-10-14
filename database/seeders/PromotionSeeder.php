<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotion;
use Illuminate\Support\Str;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            [
                'code' => 'WELCOME10',
                'name' => 'Welcome 10% off',
                'status' => 'ACTIVE',
                'promotion_type' => 'PERCENT',
                'promotion_value' => 10,
                'is_max_promotion_value' => true,
                'max_promotion_value' => 50000,
                'order_min_value' => 200000,
                'start_date' => $now->copy()->subDays(1),
                'end_date' => $now->copy()->addMonths(1),
                'qty_limit' => 1000,
                'is_once_per_customer' => true,
                'note' => 'For new customers',
                'is_deleted' => 0,
            ],
            [
                'code' => 'FREESHIP',
                'name' => 'Free Shipping 30k',
                'status' => 'ACTIVE',
                'promotion_type' => 'AMOUNT',
                'promotion_value' => 30000,
                'is_max_promotion_value' => false,
                'max_promotion_value' => null,
                'order_min_value' => 150000,
                'start_date' => $now->copy()->subDays(7),
                'end_date' => $now->copy()->addMonths(2),
                'qty_limit' => null,
                'is_once_per_customer' => false,
                'note' => 'Auto applies at checkout',
                'is_deleted' => 0,
            ],
            [
                'code' => 'SUMMER50K',
                'name' => 'Summer discount 50k',
                'status' => 'INACTIVE',
                'promotion_type' => 'AMOUNT',
                'promotion_value' => 50000,
                'is_max_promotion_value' => false,
                'max_promotion_value' => null,
                'order_min_value' => 300000,
                'start_date' => $now->copy()->addDays(5),
                'end_date' => $now->copy()->addMonths(1),
                'qty_limit' => 200,
                'is_once_per_customer' => false,
                'note' => 'Upcoming campaign',
                'is_deleted' => 0,
            ],
        ];

        foreach ($rows as $data) {
            Promotion::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}


