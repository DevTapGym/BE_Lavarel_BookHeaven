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
            ['name' => 'auth logout', 'guard_name' => 'api', 'apiPath' => '/v1/auth/logout', 'method' => 'POST', 'module' => 'Auth'],
            ['name' => 'auth me', 'guard_name' => 'api', 'apiPath' => '/v1/auth/me', 'method' => 'GET', 'module' => 'Auth'],
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
