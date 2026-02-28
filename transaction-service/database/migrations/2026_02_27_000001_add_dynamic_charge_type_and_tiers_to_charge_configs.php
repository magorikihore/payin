<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change charge_type enum to include 'dynamic'
        DB::statement("ALTER TABLE charge_configs MODIFY COLUMN charge_type ENUM('fixed', 'percentage', 'dynamic') DEFAULT 'fixed'");

        Schema::table('charge_configs', function (Blueprint $table) {
            $table->json('tiers')->nullable()->after('charge_value');
        });
    }

    public function down(): void
    {
        Schema::table('charge_configs', function (Blueprint $table) {
            $table->dropColumn('tiers');
        });

        DB::statement("ALTER TABLE charge_configs MODIFY COLUMN charge_type ENUM('fixed', 'percentage') DEFAULT 'fixed'");
    }
};
