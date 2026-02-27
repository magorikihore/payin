<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('platform_charge', 15, 2)->default(0)->after('amount');
            $table->decimal('operator_charge', 15, 2)->default(0)->after('platform_charge');
            $table->string('operator_receipt')->nullable()->unique()->after('operator');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['platform_charge', 'operator_charge', 'operator_receipt']);
        });
    }
};
