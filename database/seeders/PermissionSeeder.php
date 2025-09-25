<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'logout', 'guard_name' => 'api', 'apiPath' => '/v1/auth/logout', 'method' => 'POST', 'module' => 'Auth'],
            ['name' => 'get info', 'guard_name' => 'api', 'apiPath' => '/v1/auth/me', 'method' => 'GET', 'module' => 'Auth'],

            ['name' => 'view customers', 'guard_name' => 'api', 'apiPath' => '/v1/customer', 'method' => 'GET', 'module' => 'Customer'],
            ['name' => 'show customer', 'guard_name' => 'api', 'apiPath' => '/v1/customer/{customer}', 'method' => 'GET', 'module' => 'Customer'],
            ['name' => 'create customer', 'guard_name' => 'api', 'apiPath' => '/v1/customer', 'method' => 'POST', 'module' => 'Customer'],
            ['name' => 'update customer', 'guard_name' => 'api', 'apiPath' => '/v1/customer/{customer}', 'method' => 'PUT', 'module' => 'Customer'],
            ['name' => 'delete customer', 'guard_name' => 'api', 'apiPath' => '/v1/customer/{customer}', 'method' => 'DELETE', 'module' => 'Customer'],

            ['name' => 'view employees', 'guard_name' => 'api', 'apiPath' => '/v1/employee', 'method' => 'GET', 'module' => 'Employee'],
            ['name' => 'show employee', 'guard_name' => 'api', 'apiPath' => '/v1/employee/{employee}', 'method' => 'GET', 'module' => 'Employee'],
            ['name' => 'create employee', 'guard_name' => 'api',  'apiPath' => '/v1/employee',  'method' => 'POST',  'module' =>  'Employee'],
            ['name' =>  'update employee',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/employee',  'method'  =>  'PUT',  'module'  =>  'Employee'],
            ['name' =>  'delete employee',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/employee/{employee}',  'method'  =>  'DELETE',  'module'  =>  'Employee'],


            ['name' => 'view cart items', 'guard_name' => 'api', 'apiPath' => '/v1/cart/{customer_id}', 'method' => 'GET', 'module' => 'Cart'],
            ['name' => 'create cart', 'guard_name' => 'api', 'apiPath' => '/v1/cart', 'method' => 'POST', 'module' => 'Cart'],
            ['name' => 'add cart item', 'guard_name' => 'api', 'apiPath' => '/v1/cart/add-item', 'method' => 'POST', 'module' => 'Cart'],
            ['name' => 'update cart item', 'guard_name' => 'api', 'apiPath' => '/v1/cart/update-item/{cart_item_id}', 'method' => 'PUT', 'module' => 'Cart'],
            ['name' => 'remove cart item', 'guard_name' => 'api', 'apiPath' => '/v1/cart/remove-item/{cart_item_id}', 'method' => 'DELETE', 'module' => 'Cart'],
        ];

        $added = false;

        foreach ($permissions as $perm) {
            $permission = Permission::updateOrCreate(
                ['name' => $perm['name'], 'guard_name' => $perm['guard_name']],
                $perm
            );

            if ($permission->wasRecentlyCreated) {
                $added = true;
            }
        }

        if ($added) {
            $this->command->info('Some permissions have been seeded successfully!');
        } else {
            $this->command->warn('Permissions already exist. Seeder skipped!');
        }
    }
}
