<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $transactions = [
            [
                'user_id' => 1,
                'transaction_ref' => 'TXN-' . strtoupper(uniqid()),
                'amount' => 50000.00,
                'currency' => 'TZS',
                'type' => 'collection',
                'status' => 'completed',
                'description' => 'Collection via M-Pesa',
                'payment_method' => 'mobile_money',
                'operator' => 'M-Pesa',
            ],
            [
                'user_id' => 1,
                'transaction_ref' => 'TXN-' . strtoupper(uniqid()),
                'amount' => 15000.00,
                'currency' => 'TZS',
                'type' => 'disbursement',
                'status' => 'completed',
                'description' => 'Disbursement to customer via Tigo Pesa',
                'payment_method' => 'mobile_money',
                'operator' => 'Tigo Pesa',
            ],
            [
                'user_id' => 1,
                'transaction_ref' => 'TXN-' . strtoupper(uniqid()),
                'amount' => 200000.00,
                'currency' => 'TZS',
                'type' => 'topup',
                'status' => 'completed',
                'description' => 'Topup transfer via M-Pesa',
                'payment_method' => 'mobile_money',
                'operator' => 'M-Pesa',
            ],
            [
                'user_id' => 1,
                'transaction_ref' => 'TXN-' . strtoupper(uniqid()),
                'amount' => 8500.00,
                'currency' => 'TZS',
                'type' => 'collection',
                'status' => 'pending',
                'description' => 'Collection via Halopesa',
                'payment_method' => 'mobile_money',
                'operator' => 'Halopesa',
            ],
            [
                'user_id' => 1,
                'transaction_ref' => 'TXN-' . strtoupper(uniqid()),
                'amount' => 75000.00,
                'currency' => 'TZS',
                'type' => 'settlement',
                'status' => 'completed',
                'description' => 'Settlement withdrawal via Tigo Pesa',
                'payment_method' => 'bank_transfer',
                'operator' => 'Tigo Pesa',
            ],
            [
                'user_id' => 1,
                'transaction_ref' => 'TXN-' . strtoupper(uniqid()),
                'amount' => 5000.00,
                'currency' => 'TZS',
                'type' => 'collection',
                'status' => 'completed',
                'description' => 'Collection via Airtel Money',
                'payment_method' => 'mobile_money',
                'operator' => 'Airtel Money',
            ],
            [
                'user_id' => 1,
                'transaction_ref' => 'TXN-' . strtoupper(uniqid()),
                'amount' => 120000.00,
                'currency' => 'TZS',
                'type' => 'disbursement',
                'status' => 'failed',
                'description' => 'Disbursement failed - Airtel Money',
                'payment_method' => 'mobile_money',
                'operator' => 'Airtel Money',
            ],
            [
                'user_id' => 1,
                'transaction_ref' => 'TXN-' . strtoupper(uniqid()),
                'amount' => 30000.00,
                'currency' => 'TZS',
                'type' => 'collection',
                'status' => 'completed',
                'description' => 'Collection via Airtel Money',
                'payment_method' => 'mobile_money',
                'operator' => 'Airtel Money',
            ],
            [
                'user_id' => 1,
                'transaction_ref' => 'TXN-' . strtoupper(uniqid()),
                'amount' => 95000.00,
                'currency' => 'TZS',
                'type' => 'topup',
                'status' => 'completed',
                'description' => 'Topup transfer via Halopesa',
                'payment_method' => 'mobile_money',
                'operator' => 'Halopesa',
            ],
            [
                'user_id' => 1,
                'transaction_ref' => 'TXN-' . strtoupper(uniqid()),
                'amount' => 45000.00,
                'currency' => 'TZS',
                'type' => 'settlement',
                'status' => 'completed',
                'description' => 'Settlement withdrawal via M-Pesa',
                'payment_method' => 'bank_transfer',
                'operator' => 'M-Pesa',
            ],
        ];

        foreach ($transactions as $txn) {
            Transaction::create($txn);
        }
    }
}
