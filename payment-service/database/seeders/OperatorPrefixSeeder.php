<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Operator;

class OperatorPrefixSeeder extends Seeder
{
    /**
     * Seed operator phone prefixes for Tanzania.
     *
     * Phone format: 255XXXXXXXXX (country code + 9 digits)
     * Prefixes stored as the digits AFTER country code 255.
     *
     * Vodacom (M-Pesa):  74, 75, 76
     * Tigo (Tigo Pesa):  65, 67, 71
     * Airtel (Airtel Money): 68, 69, 78
     * Halotel (Halopesa):  62, 61
     */
    public function run(): void
    {
        $prefixMap = [
            'mpesa'       => ['74', '75', '76'],
            'tigopesa'    => ['65', '67', '71'],
            'airtelmoney' => ['68', '69', '78'],
            'halopesa'    => ['62', '61'],
        ];

        foreach ($prefixMap as $code => $prefixes) {
            Operator::where('code', $code)->update([
                'prefixes' => json_encode($prefixes),
            ]);
        }
    }
}
