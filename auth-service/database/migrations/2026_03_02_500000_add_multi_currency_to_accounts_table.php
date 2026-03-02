<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('multi_currency_enabled')->default(false)->after('currency');
            $table->json('allowed_currencies')->nullable()->after('multi_currency_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['multi_currency_enabled', 'allowed_currencies']);
        });
    }
};
