<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change status enum to include pending_approval
        DB::statement("ALTER TABLE payment_requests MODIFY COLUMN status ENUM('pending','pending_approval','processing','completed','failed','cancelled','timeout','rejected') DEFAULT 'pending'");

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('transaction_id');
            $table->unsignedBigInteger('approved_by')->nullable()->after('created_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->string('approval_notes')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payment_requests MODIFY COLUMN status ENUM('pending','processing','completed','failed','cancelled','timeout') DEFAULT 'pending'");

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'approved_by', 'approved_at', 'approval_notes']);
        });
    }
};
