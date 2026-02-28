<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Operator;

class OperatorPrefixSeeder extends Seeder
{
    /**
     * Seed operator records with phone prefixes for Tanzania.
     *
     * Phone format: 0XXXXXXXXX (10 digits) or 255XXXXXXXXX (12 digits)
     * Prefixes stored as 3-digit local format (e.g., 075, 065).
     *
     * Vodacom (M-Pesa):  074, 075, 076
     * Tigo (Tigo Pesa):  065, 067, 071
     * Airtel (Airtel Money): 068, 069, 078
     * Halotel (Halopesa):  062, 061
     */
    public function run(): void
    {
        $operators = [
            [
                'name'     => 'M-Pesa',
                'code'     => 'mpesa',
                'prefixes' => ['074', '075', '076'],
                'api_url'  => 'https://api.vodacom.co.tz',
            ],
            [
                'name'     => 'Tigo Pesa',
                'code'     => 'tigopesa',
                'prefixes' => ['065', '067', '071'],
                'api_url'  => 'https://api.tigo.co.tz',
            ],
            [
                'name'     => 'Airtel Money',
                'code'     => 'airtelmoney',
                'prefixes' => ['068', '069', '078'],
                'api_url'  => 'https://api.airtel.co.tz',
            ],
            [
                'name'     => 'Halopesa',
                'code'     => 'halopesa',
                'prefixes' => ['062', '061'],
                'api_url'  => 'https://api.halotel.co.tz',
            ],
        ];

        foreach ($operators as $op) {
            Operator::updateOrCreate(
                ['code' => $op['code']],
                [
                    'name'     => $op['name'],
                    'prefixes' => $op['prefixes'],
                    'api_url'  => $op['api_url'],
                    'status'   => 'active',
                ]
            );
        }
    }
}
