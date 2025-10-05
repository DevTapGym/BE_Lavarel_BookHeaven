<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AddressTag;

class AddressTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $addressTags = [
            [
                'name'    => 'Home',
            ],
            [
                'name'    => 'Office',
            ],
            [
                'name'    => 'School',
            ],
            [
                'name'    => 'Company',
            ],
            [
                'name'    => 'Store',
            ],
            [
                'name'    => 'Other',
            ],
        ];

        foreach ($addressTags as $tag) {
            AddressTag::create($tag);
        }
    }
}
