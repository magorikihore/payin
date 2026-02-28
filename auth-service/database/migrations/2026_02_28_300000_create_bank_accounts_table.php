<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('bank_name', 191);
            $table->string('account_name', 191);
            $table->string('account_number', 191);
            $table->string('swift_code', 191)->nullable();
            $table->string('branch', 191)->nullable();
            $table->string('label', 100)->nullable(); // e.g. "Main Account", "Payroll"
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
