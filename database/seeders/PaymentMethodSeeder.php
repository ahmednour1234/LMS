<?php

namespace Database\Seeders;

use App\Domain\Accounting\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Cash',
                'code' => 'CASH',
                'type' => 'cash',
                'is_active' => true,
                'config' => null,
            ],
            [
                'name' => 'Bank Transfer',
                'code' => 'BANK_TRANSFER',
                'type' => 'bank_transfer',
                'is_active' => true,
                'config' => [
                    'bank_name' => 'Default Bank',
                    'account_number' => '1234567890',
                ],
            ],
            [
                'name' => 'Credit Card',
                'code' => 'CREDIT_CARD',
                'type' => 'card',
                'is_active' => true,
                'config' => [
                    'processor' => 'stripe',
                ],
            ],
            [
                'name' => 'Debit Card',
                'code' => 'DEBIT_CARD',
                'type' => 'card',
                'is_active' => true,
                'config' => [
                    'processor' => 'stripe',
                ],
            ],
            [
                'name' => 'Online Payment Gateway',
                'code' => 'ONLINE_GATEWAY',
                'type' => 'gateway',
                'is_active' => true,
                'config' => [
                    'gateway' => 'paypal',
                    'api_key' => 'demo_key',
                ],
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::firstOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}

