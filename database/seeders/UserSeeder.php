<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('123456'),
            ]
        );
        $admin->assignRole('admin');

        $isNew = $admin->wasRecentlyCreated;

        if ($isNew) {
            $this->command->info('Users default created successfully!');
        }
    }
}
