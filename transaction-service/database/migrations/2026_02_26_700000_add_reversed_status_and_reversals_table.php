<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'reversed' to the status enum
        DB::statement("ALTER TABLE transactions MODIFY COLUMN `status` ENUM('pending','completed','failed','cancelled','reversed') DEFAULT 'pending'");

        // Create reversals table
        Schema::create('reversals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->index();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('reversal_ref')->unique();
            $table->string('original_ref');
            $table->decimal('amount', 15, 2);
            $table->decimal('platform_charge', 15, 2)->default(0);
            $table->decimal('operator_charge', 15, 2)->default(0);
            $table->string('type'); // collection or disbursement
            $table->string('operator')->nullable();
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN `status` ENUM('pending','completed','failed','cancelled') DEFAULT 'pending'");
        Schema::dropIfExists('reversals');
    }
};
