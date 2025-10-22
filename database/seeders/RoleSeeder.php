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
        $admin = Role::updateOrCreate(
            ['name' => 'ADMIN', 'guard_name' => 'api'],
            ['description' => 'Admin thì có tất cả quyền']
        );
        $admin->syncPermissions(Permission::all());
        $this->command->info('Roles have been seeded successfully!');

        $customer = Role::updateOrCreate(
            ['name' => 'CUSTOMER', 'guard_name' => 'api'],
            ['description' => 'Khách hàng với các quyền cơ bản']
        );
        $customer->syncPermissions(Permission::whereIn('name', [
            'logout',
            'get info',
            'get account',
            'edit profile',
            'change password',
            'upload avatar',
            'view my cart',
            'view cart items',
            'toggle cart item is select',
            'create cart',
            'add cart item',
            'add cart item for web',
            'update cart item',
            'remove cart item',
            'view address tags',
            'view customer addresses',
            'create shipping address',
            'update shipping address',
            'delete shipping address',
            'view payment methods',
            'view orders',
            'view user orders',
            'create order',
            'create order for web',
            'view orders history',
            'create order',
            'place order',
            'place order for web',
            'return order',
            'show order',
            'update order',
            'view accounts',
            'show account',
            'create account',
            'update account',
            'update customer',
            'return order',
        ])->get());
        $this->command->info('Roles have been seeded successfully!');

        $employee = Role::updateOrCreate(
            ['name' => 'EMPLOYEE', 'guard_name' => 'api'],
            ['description' => 'Nhân viên với các quyền quản lý cơ bản']
        );
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
