<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $payments = [
            [
                'name' => 'Cash on Delivery',
                'is_active' => true,
                'provider' => 'Local',
                'type' => 'COD',
                'logo_url' => null,
            ],
            [
                'name' => 'Momo E-Wallet',
                'is_active' => false,
                'provider' => 'Momo',
                'type' => 'E-Wallet',
                'logo_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQZcQPC-zWVyFOu9J2OGl0j2D220D49D0Z7BQ&s',
            ],
            [
                'name' => 'ZaloPay E-Wallet',
                'is_active' => false,
                'provider' => 'ZaloPay',
                'type' => 'E-Wallet',
                'logo_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRwPynD27LbXlPsbofv1AX-5ZXDn_XMGo-1TA&s',
            ],
            [
                'name' => 'VN Pay',
                'is_active' => false,
                'provider' => 'VN Pay',
                'type' => 'Online Payment',
                'logo_url' => 'https://play-lh.googleusercontent.com/htxII9LeOz8fRkdW0pcvOb88aoc448v9eoxnbKEPK98NLG6iX5mSd4dbu3PX9j36dwy9=w480-h960-rw',
            ],
        ];

        foreach ($payments as $payment) {
            PaymentMethod::create($payment);
        }
    }
}
