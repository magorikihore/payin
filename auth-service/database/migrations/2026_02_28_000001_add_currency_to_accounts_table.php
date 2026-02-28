<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('currency', 10)->default('TZS')->after('country');
        });

        // Set default currencies based on country
        \DB::statement("UPDATE accounts SET currency = CASE
            WHEN country = 'Kenya' THEN 'KES'
            WHEN country = 'Uganda' THEN 'UGX'
            WHEN country = 'Rwanda' THEN 'RWF'
            WHEN country = 'Burundi' THEN 'BIF'
            WHEN country = 'DRC' THEN 'CDF'
            WHEN country = 'Mozambique' THEN 'MZN'
            WHEN country = 'Malawi' THEN 'MWK'
            WHEN country = 'Zambia' THEN 'ZMW'
            WHEN country = 'South Africa' THEN 'ZAR'
            WHEN country = 'Nigeria' THEN 'NGN'
            WHEN country = 'Ghana' THEN 'GHS'
            ELSE 'TZS'
        END");
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
