<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::updateOrCreate(['name' => 'admin', 'guard_name' => 'api', 'description' => 'Administrator with full permissions']);
        $admin->syncPermissions(Permission::all());
        $this->command->info('Roles have been seeded successfully!');

        $customer = Role::updateOrCreate(['name' => 'customer', 'guard_name' => 'api', 'description' => 'Customer with limited permissions']);
        $customer->syncPermissions(Permission::whereIn('name', [
            'view categories',
            'view category',
            'view products',
            'view product'
        ])->get());
        $this->command->info('Roles have been seeded successfully!');

        $employee = Role::updateOrCreate(['name' => 'employee', 'guard_name' => 'api', 'description' => 'Employee with specific permissions']);
        $employee->syncPermissions(Permission::whereIn('name', [
            'view categories',
            'view category',
            'view products',
            'view product',
            'create product',
            'update product',
            'delete product',
            'view orders',
            'view order',
        ])->get());
        $this->command->info('Roles have been seeded successfully!');
    }
}
