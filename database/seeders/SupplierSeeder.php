<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name'    => 'Nhà sách Fahasa',
                'address' => '60-62 Lê Lợi, Q.1, TP. Hồ Chí Minh',
                'email'   => 'contact@fahasa.com',
                'phone'   => '0283822000',
            ],
            [
                'name'    => 'Công ty CP Sách Thái Hà',
                'address' => '53 Nguyễn Du, Hai Bà Trưng, Hà Nội',
                'email'   => 'info@thaihabooks.com',
                'phone'   => '0243943946',
            ],
            [
                'name'    => 'NXB Trẻ',
                'address' => '161B Lý Chính Thắng, Q.3, TP. Hồ Chí Minh',
                'email'   => 'info@nxbtre.com.vn',
                'phone'   => '0283931628',
            ],
            [
                'name'    => 'Công ty CP Văn hóa Phương Nam',
                'address' => '940 Đường 3/2, Q.11, TP. Hồ Chí Minh',
                'email'   => 'info@pnc.com.vn',
                'phone'   => '0283863205',
            ],
            [
                'name'    => 'Nhà sách Cá Chép',
                'address' => '211-213 Võ Văn Tần, Q.3, TP. Hồ Chí Minh',
                'email'   => 'contact@cachep.vn',
                'phone'   => '0283930963',
            ],
            [
                'name'    => 'Công ty CP Văn hóa và Truyền thông Nhã Nam',
                'address' => '59 Đỗ Quang, Trung Hòa, Cầu Giấy, Hà Nội',
                'email'   => 'info@nhanam.vn',
                'phone'   => '0243514682',
            ],
            [
                'name'    => 'Alpha Books',
                'address' => '176 Thái Hà, Đống Đa, Hà Nội',
                'email'   => 'contact@alphabooks.vn',
                'phone'   => '0243514609',
            ],
            [
                'name'    => 'MCBooks',
                'address' => '63A Trần Quốc Hoàn, Cầu Giấy, Hà Nội',
                'email'   => 'support@mcbooks.vn',
                'phone'   => '0243793083',
            ],
            [
                'name'    => 'First News - Trí Việt',
                'address' => '11H Nguyễn Thị Minh Khai, Q.1, TP. Hồ Chí Minh',
                'email'   => 'info@firstnews.com.vn',
                'phone'   => '0283829797',
            ],
            [
                'name'    => 'Công ty CP Sách Alpha (Alphabooks)',
                'address' => '176 Thái Hà, Đống Đa, Hà Nội',
                'email'   => 'alphabooks@alphabooks.vn',
                'phone'   => '0243514608',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }

        $this->command->info('Suppliers have been seeded successfully!');
    }
}
