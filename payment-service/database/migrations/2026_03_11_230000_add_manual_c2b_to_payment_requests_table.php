<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Add manual_c2b to type enum
        DB::statement("ALTER TABLE payment_requests MODIFY COLUMN `type` ENUM('collection','disbursement','manual_c2b') NOT NULL");

        // Add waiting and expired to status enum
        DB::statement("ALTER TABLE payment_requests MODIFY COLUMN `status` ENUM('pending','pending_approval','processing','completed','failed','cancelled','timeout','rejected','waiting','expired') DEFAULT 'pending'");

        // Make phone, operator_code, operator_name nullable (invoices don't have them initially)
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
            $table->string('operator_code')->nullable()->change();
            $table->string('operator_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payment_requests MODIFY COLUMN `type` ENUM('collection','disbursement') NOT NULL");
        DB::statement("ALTER TABLE payment_requests MODIFY COLUMN `status` ENUM('pending','pending_approval','processing','completed','failed','cancelled','timeout','rejected') DEFAULT 'pending'");

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
            $table->string('operator_code')->nullable(false)->change();
            $table->string('operator_name')->nullable(false)->change();
        });
    }
};
