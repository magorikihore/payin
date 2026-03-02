<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->string('gateway_type', 50)->default('digivas')->after('code');
            $table->string('country', 5)->default('TZ')->after('gateway_type');
            $table->string('country_code', 10)->default('255')->after('country');
            $table->string('currency', 10)->default('TZS')->after('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropColumn(['gateway_type', 'country', 'country_code', 'currency']);
        });
    }
};
