<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $customer = Customer::firstOrCreate(
            ['email' => 'customer@gmail.com'],
            [
                'name' => 'Default Customer',
                'phone' => '0998887652',
                'address' => 'Test Address',
            ]
        );

        Cart::create(['customer_id' => $customer->id]);

        $employee = Employee::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Default Employee',
                'phone' => '0998887653',
                'address' => 'Test Address',
                'position' => 'Staff',
                'date_of_birth' => '1990-01-01',
                'salary' => 50000,
                'hire_date' => '2020-01-01',
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('123456'),
                'is_active' => true,
                'employee_id' => $employee->id,
                'customer_id' => $customer->id,
            ]
        );
        $admin->assignRole('admin');

        $isNew = $admin->wasRecentlyCreated;

        if ($isNew) {
            $this->command->info('Users default created successfully!');
        }
    }
}
