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
            ['name' => 'edit profile', 'guard_name' => 'api', 'apiPath' => '/v1/auth/edit-profile', 'method' => 'PUT', 'module' => 'Auth'],
            ['name' => 'change password', 'guard_name' => 'api', 'apiPath' => '/v1/auth/change-password', 'method' => 'PUT', 'module' => 'Auth'],
            ['name' => 'get account', 'guard_name' => 'api', 'apiPath' => '/v1/auth/account', 'method' => 'GET', 'module' => 'Auth'],

            ['name' => 'login', 'guard_name' => 'api', 'apiPath' => '/v1/auth/login', 'method' => 'POST', 'module' => 'Auth'],
            ['name' => 'register', 'guard_name' => 'api', 'apiPath' => '/v1/auth/register', 'method' => 'POST', 'module' => 'Auth'],
            ['name' => 'refresh token', 'guard_name' => 'api', 'apiPath' => '/v1/auth/refresh', 'method' => 'POST', 'module' => 'Auth'],
            ['name' => 'forgot password', 'guard_name' => 'api', 'apiPath' => '/v1/auth/forgot-password', 'method' => 'POST', 'module' => 'Auth'],
            ['name' => 'reset password', 'guard_name' => 'api',  'apiPath'  =>  '/v1/auth/reset-password',  'method'  =>  'POST',  'module'  =>  'Auth'],


            ['name' => 'upload avatar', 'guard_name' => 'api', 'apiPath' => '/v1/upload/avatar', 'method' => 'POST', 'module' => 'Upload'],
            ['name' => 'upload book image', 'guard_name' => 'api', 'apiPath' => '/v1/upload/book-image', 'method' => 'POST', 'module' => 'Upload'],
            ['name' => 'upload thumbnail book', 'guard_name' => 'api', 'apiPath' => '/v1/upload/thumbnail', 'method' => 'POST', 'module' => 'Upload'],
            ['name' => 'upload logo payment method', 'guard_name' => 'api', 'apiPath' => '/v1/upload/logo-payment', 'method' => 'POST', 'module' => 'Upload'],

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
            ['name' => 'view my cart', 'guard_name' => 'api', 'apiPath' => '/v1/cart/my-cart', 'method' => 'GET', 'module' => 'Cart'],
            ['name' => 'create cart', 'guard_name' => 'api', 'apiPath' => '/v1/cart', 'method' => 'POST', 'module' => 'Cart'],
            ['name' => 'add cart item', 'guard_name' => 'api', 'apiPath' => '/v1/cart/add-item', 'method' => 'POST', 'module' => 'Cart'],
            ['name' => 'update cart item', 'guard_name' => 'api', 'apiPath' => '/v1/cart/update-item/{cart_item_id}', 'method' => 'PUT', 'module' => 'Cart'],
            ['name' => 'remove cart item', 'guard_name' => 'api', 'apiPath' => '/v1/cart/remove-item/{cart_item_id}', 'method' => 'DELETE', 'module' => 'Cart'],

            ['name' => 'create book', 'guard_name' => 'api', 'apiPath' => '/v1/book', 'method' => 'POST', 'module' => 'Book'],
            ['name' => 'update book', 'guard_name' => 'api', 'apiPath' => '/v1/book', 'method' => 'PUT', 'module' => 'Book'],
            ['name' => 'delete book', 'guard_name' => 'api', 'apiPath' => '/v1/book/{book}', 'method' => 'DELETE', 'module' => 'Book'],
            ['name' => 'attach book categories', 'guard_name' => 'api', 'apiPath' => '/v1/book/attach-categories', 'method' => 'POST', 'module' => 'Book'],
            ['name' => 'sync book categories', 'guard_name' => 'api', 'apiPath' => '/v1/book/sync-categories', 'method' => 'PUT', 'module' => 'Book'],
            ['name' => 'detach book categories', 'guard_name' => 'api', 'apiPath' => '/v1/book/detach-categories', 'method' => 'DELETE', 'module' => 'Book'],

            ['name' => 'create category', 'guard_name' => 'api', 'apiPath' => '/v1/category', 'method' => 'POST', 'module' => 'Category'],
            ['name' => 'update category', 'guard_name' => 'api', 'apiPath' => '/v1/category', 'method' => 'PUT', 'module' => 'Category'],
            ['name' => 'delete category', 'guard_name' => 'api', 'apiPath' => '/v1/category/{category}', 'method' => 'DELETE', 'module' => 'Category'],

            ['name' => 'view book images', 'guard_name' => 'api', 'apiPath' => '/v1/book/images/{book_id}', 'method' => 'GET', 'module' => 'BookImage'],
            ['name' => 'add book images', 'guard_name' => 'api', 'apiPath' => '/v1/book/images', 'method' => 'POST', 'module' => 'BookImage'],
            ['name' => 'delete book image', 'guard_name' => 'api', 'apiPath' => '/v1/book/images/{image_id}', 'method' => 'DELETE', 'module' => 'BookImage'],
            ['name' => 'delete all book images', 'guard_name' => 'api', 'apiPath' => '/v1/book/images/book/{book_id}', 'method' => 'DELETE', 'module' => 'BookImage'],

            ['name' => 'view book features', 'guard_name' => 'api', 'apiPath' => '/v1/book/feature/{book_id}', 'method' => 'GET', 'module' => 'BookFeature'],
            ['name' => 'add book feature', 'guard_name' => 'api', 'apiPath' => '/v1/book/feature', 'method' => 'POST', 'module' => 'BookFeature'],
            ['name' => 'update book feature', 'guard_name' => 'api', 'apiPath' => '/v1/book/feature', 'method' => 'PUT', 'module' => 'BookFeature'],
            ['name' => 'delete book feature', 'guard_name' => 'api', 'apiPath' => '/v1/book/feature/{feature_id}', 'method' => 'DELETE', 'module' => 'BookFeature'],
            ['name' => 'delete all book features', 'guard_name' => 'api', 'apiPath' => '/v1/book/feature/book/{book_id}', 'method' => 'DELETE', 'module' => 'BookFeature'],

            ['name' => 'view suppliers', 'guard_name' => 'api', 'apiPath' => '/v1/supplier', 'method' => 'GET', 'module' => 'Supplier'],
            ['name' => 'show supplier', 'guard_name' => 'api', 'apiPath' => '/v1/supplier/{id}', 'method' => 'GET', 'module' => 'Supplier'],
            ['name' => 'show supplier books', 'guard_name' => 'api', 'apiPath' => '/v1/supplier/{id}/supplies', 'method' => 'GET', 'module' => 'Supplier'],
            ['name' => 'show supplier supplies', 'guard_name' => 'api', 'apiPath' => '/v1/supplier/{id}/supplies', 'method' => 'GET', 'module' => 'Supplier'],
            ['name' => 'create supplier', 'guard_name' => 'api', 'apiPath' => '/v1/supplier', 'method' => 'POST', 'module' => 'Supplier'],
            ['name' => 'update supplier', 'guard_name' => 'api', 'apiPath' => '/v1/supplier', 'method' => 'PUT', 'module' => 'Supplier'],
            ['name' => 'delete supplier', 'guard_name' => 'api', 'apiPath' => '/v1/supplier/{supplier}', 'method' => 'DELETE', 'module' => 'Supplier'],

            ['name' => 'view supplies', 'guard_name' => 'api', 'apiPath' => '/v1/supply', 'method' => 'GET', 'module' => 'Supply'],
            ['name' =>  'show supply',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/supply/{supply}',  'method'  =>  'GET',  'module'  =>  'Supply'],
            ['name' =>  'create supply',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/supply',  'method'  =>  'POST',  'module'  =>  'Supply'],
            ['name' =>  'update supply',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/supply',  'method'  =>  'PUT',  'module'  =>  'Supply'],
            ['name' =>  'delete supply',  'guard_name'  =>  'api',  'apiPath' =>  '/v1/supply/{supply}',  'method'  =>  'DELETE',  'module'  =>  'Supply'],

            ['name' => 'view import receipts', 'guard_name' => 'api', 'apiPath' => '/v1/import-receipt', 'method' => 'GET', 'module' => 'ImportReceipt'],
            ['name' => 'show import receipt', 'guard_name' => 'api', 'apiPath' => '/v1/import-receipt/{import_receipt}', 'method' => 'GET', 'module' => 'ImportReceipt'],
            ['name' => 'create import receipt', 'guard_name' => 'api', 'apiPath' => '/v1/import-receipt', 'method' => 'POST', 'module' => 'ImportReceipt'],
            ['name' => 'update import receipt', 'guard_name' => 'api', 'apiPath' => '/v1/import-receipt', 'method' => 'PUT', 'module' => 'ImportReceipt'],

            ['name' => 'view address tags', 'guard_name' => 'api', 'apiPath' => '/v1/address-tag', 'method' => 'GET', 'module' => 'AddressTag'],
            ['name' => 'create address tag', 'guard_name' => 'api', 'apiPath' => '/v1/address-tag', 'method' => 'POST', 'module' => 'AddressTag'],
            ['name' => 'update address tag', 'guard_name' => 'api', 'apiPath' => '/v1/address-tag', 'method' => 'PUT', 'module' => 'AddressTag'],
            ['name' => 'delete address tag', 'guard_name' => 'api', 'apiPath' => '/v1/address-tag/{addressTag}', 'method' => 'DELETE', 'module' => 'AddressTag'],

            ['name' => 'view payment methods', 'guard_name' => 'api', 'apiPath' => '/v1/payment-method', 'method' => 'GET', 'module' => 'PaymentMethod'],
            ['name' => 'create payment method', 'guard_name' => 'api', 'apiPath' => '/v1/payment-method', 'method' => 'POST', 'module' => 'PaymentMethod'],
            ['name' => 'update payment method', 'guard_name' => 'api', 'apiPath' => '/v1/payment-method', 'method' => 'PUT', 'module' => 'PaymentMethod'],
            ['name' => 'delete payment method', 'guard_name' => 'api', 'apiPath' => '/v1/payment-method/{paymentMethod}', 'method' => 'DELETE', 'module' => 'PaymentMethod'],

            ['name' => 'view customer addresses', 'guard_name' => 'api', 'apiPath' => '/v1/address/customer', 'method' => 'GET', 'module' => 'ShippingAddress'],
            ['name' => 'create shipping address', 'guard_name' => 'api', 'apiPath' => '/v1/address', 'method' => 'POST', 'module' => 'ShippingAddress'],
            ['name' => 'update shipping address', 'guard_name' => 'api', 'apiPath' => '/v1/address/{id}', 'method' => 'PUT', 'module' => 'ShippingAddress'],
            ['name' => 'delete shipping address', 'guard_name' => 'api', 'apiPath' => '/v1/address/{id}', 'method' => 'DELETE', 'module' => 'ShippingAddress'],

            ['name' => 'view order statuses', 'guard_name' => 'api', 'apiPath' => '/v1/order-status', 'method' => 'GET', 'module' => 'OrderStatus'],
            ['name' => 'create order status', 'guard_name' => 'api', 'apiPath' => '/v1/order-status', 'method' => 'POST', 'module' => 'OrderStatus'],
            ['name' => 'update order status', 'guard_name' => 'api', 'apiPath' => '/v1/order-status', 'method' => 'PUT', 'module' => 'OrderStatus'],
            ['name' => 'delete order status', 'guard_name' => 'api', 'apiPath' => '/v1/order-status/{orderStatus}', 'method' => 'DELETE', 'module' => 'OrderStatus'],

            ['name' => 'view order status histories', 'guard_name' => 'api',  'apiPath'  =>  '/v1/order-status-history/order/{orderId}',  'method'  =>  'GET',  'module'  =>  'OrderStatusHistory'],
            ['name' =>  'create order status history',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/order-status-history',  'method'  =>  'POST',  'module'  =>  'OrderStatusHistory'],
            ['name' =>  'update order status history',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/order-status-history',  'method'  =>  'PUT',  'module'  =>  'OrderStatusHistory'],
            ['name' =>  'delete order status history',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/order-status-history/{orderStatusHistory}',  'method'  =>  'DELETE',  'module'  =>  'OrderStatusHistory'],

            ['name' => 'view orders', 'guard_name' => 'api', 'apiPath' => '/v1/order', 'method' => 'GET', 'module' => 'Order'],
            ['name' => 'view user orders', 'guard_name' => 'api', 'apiPath' => '/v1/order/user', 'method' => 'GET', 'module' => 'Order'],
            ['name' => 'show order', 'guard_name' => 'api', 'apiPath' => '/v1/order/{order}', 'method' => 'GET', 'module' => 'Order'],
            ['name' => 'create order', 'guard_name' => 'api', 'apiPath' => '/v1/order/create', 'method' => 'POST', 'module' => 'Order'],
            ['name' => 'place order', 'guard_name' => 'api', 'apiPath' => '/v1/order/place', 'method' => 'POST', 'module' => 'Order'],

            ['name' => 'view roles', 'guard_name' => 'api', 'apiPath' => '/v1/role', 'method' => 'GET', 'module' => 'Role'],
            ['name' => 'show role', 'guard_name' => 'api', 'apiPath' => '/v1/role/{role}', 'method' => 'GET', 'module' => 'Role'],
            ['name' => 'create role', 'guard_name' => 'api', 'apiPath' => '/v1/role', 'method' => 'POST', 'module' => 'Role'],
            ['name' => 'update role', 'guard_name' => 'api', 'apiPath' => '/v1/role', 'method' => 'PUT', 'module' => 'Role'],
            ['name' => 'delete role', 'guard_name' => 'api', 'apiPath' => '/v1/role/{role}', 'method' => 'DELETE', 'module' => 'Role'],

            ['name' =>  'view permissions',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/permissions',  'method'  =>  'GET',  'module'  =>  'Permission'],
            ['name' =>  'view permissions no pagination',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/permissions/permissions-no-pagination',  'method'  =>  'GET',  'module'  =>  'Permission'],
            ['name' =>  'show permission by id',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/permissions/id',  'method'  =>  'GET',  'module'  =>  'Permission'],
            ['name' =>  'show permission by name',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/permissions//permissions-name',  'method'  =>  'GET',  'module'  =>  'Permission'],
            ['name' =>  'create permission',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/permissions',  'method'  =>  'POST',  'module'  =>  'Permission'],
            ['name' =>  'update permission',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/permissions',  'method'  =>  'PUT',  'module'  =>  'Permission'],
            ['name' =>  'delete permission',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/permissions/{permission}',  'method'  =>  'DELETE',  'module'  =>  'Permission'],

            ['name' => 'view accounts', 'guard_name' => 'api', 'apiPath' => '/v1/account', 'method' => 'GET', 'module' => 'Account'],
            ['name' => 'show account', 'guard_name' => 'api', 'apiPath' => '/v1/account/{user}', 'method' => 'GET', 'module' => 'Account'],
            ['name' => 'create account', 'guard_name' => 'api', 'apiPath' => '/v1/account', 'method' => 'POST', 'module' => 'Account'],
            ['name' => 'update account', 'guard_name' => 'api', 'apiPath' => '/v1/account', 'method' => 'PUT', 'module' => 'Account'],
            ['name' => 'delete account', 'guard_name' => 'api', 'apiPath' => '/v1/account/{user}', 'method' => 'DELETE', 'module' => 'Account'],
            ['name' =>  'toggle account status',  'guard_name'  =>  'api',  'apiPath'  =>  '/v1/account/toggle-status/{user}',  'method'  =>  'PUT',  'module'  =>  'Account'],

            ['name' => 'view dashboard stats', 'guard_name' => 'api', 'apiPath' => '/v1/dashboard/stats', 'method' => 'GET', 'module' => 'Dashboard'],
            ['name' => 'view dashboard counts', 'guard_name' => 'api', 'apiPath' => '/v1/dashboard/count', 'method' => 'GET', 'module' => 'Dashboard'],
            ['name' => 'view dashboard monthly revenue', 'guard_name' => 'api', 'apiPath' => '/v1/dashboard/monthly-revenue', 'method' => 'GET', 'module' => 'Dashboard'],
            ['name' => 'view dashboard top categories', 'guard_name' => 'api', 'apiPath' => '/v1/dashboard/top-category', 'method' => 'GET', 'module' => 'Dashboard'],
            ['name' => 'view dashboard top books', 'guard_name' => 'api', 'apiPath' => '/v1/dashboard/top-book', 'method' => 'GET', 'module' => 'Dashboard'],
            ['name' => 'view dashboard 9', 'guard_name' => 'api', 'apiPath' => '/v1/dashboard/view9', 'method' => 'GET', 'module' => 'Dashboard'],
            ['name' => 'view dashboard 6', 'guard_name' => 'api', 'apiPath' => '/v1/dashboard/view6', 'method' => 'GET', 'module' => 'Dashboard'],
            ['name' => 'view dashboard 1', 'guard_name' => 'api', 'apiPath' => '/v1/dashboard/view1', 'method' => 'GET', 'module' => 'Dashboard'],

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
