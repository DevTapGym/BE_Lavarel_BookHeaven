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
            'view permissions by role name',
        ])->get());
        $this->command->info('Roles have been seeded successfully!');

        $employee = Role::updateOrCreate(
            ['name' => 'EMPLOYEE', 'guard_name' => 'api'],
            ['description' => 'Nhân viên bình thường thì có thể nhập hàng, kiểm kê kho, xem thống kê cơ bản']
        );
        $employee->syncPermissions(Permission::whereIn('name', [
            'logout',
            'get info',
            'get account',
            'edit profile',
            'change password',
            'upload avatar',
            'view dashboard stats',
            'view dashboard counts',
            'view dashboard monthly revenue',
            'view dashboard top categories',
            'view dashboard top books',
            'view dashboard 9',
            'view dashboard 6',
            'view dashboard 1',
            'view dashboard top 5 books sold',
            'show supplier books',
            'view suppliers',
            'view suppliers no pagination',
            'show supplier',
            'show supplier supplies',
            'create supplier',
            'update supplier',
            'delete supplier',

            'view supplies',
            'show supply',
            'create supply',
            'update supply',
            'delete supply',

            'view import receipts',
            'show import receipt',
            'create import receipt',
            'update import receipt',

            'view accounts',
            'show account',

            'complete import receipt',
            'return import receipt',
            'view permissions by role name',
        ])->get());
        $this->command->info('Roles have been seeded successfully!');

        $salers = Role::updateOrCreate(
            ['name' => 'SALERS', 'guard_name' => 'api'],
            ['description' => 'Nhân viên bán hàng với các quyền bán hàng']
        );
        $salers->syncPermissions(Permission::whereIn('name', [
            'logout',
            'get info',
            'get account',
            'edit profile',
            'change password',
            'upload avatar',
            'view dashboard stats',
            'view dashboard counts',
            'view dashboard monthly revenue',
            'view dashboard top categories',
            'view dashboard top books',
            'view dashboard 9',
            'view dashboard 6',
            'view dashboard 1',
            'view dashboard top 5 books sold',
            'view customers',
            'create customer',
            'download order pdf',
            'create order for web',
            'view order statuses',
            'view orders',
            'update order',
            'view permissions by role name',
        ])->get());
        $this->command->info('Roles have been seeded successfully!');
    }
}
