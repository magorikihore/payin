<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('callback_logs', function (Blueprint $table) {
            $table->id();
            $table->string('operator_code')->nullable();
            $table->string('format')->nullable();
            $table->string('reference')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('status')->default('unmatched');
            $table->unsignedBigInteger('payment_request_id')->nullable();
            $table->json('raw_payload');
            $table->json('parsed_data')->nullable();
            $table->string('response_code')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index('reference');
            $table->index('operator_code');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('callback_logs');
    }
};
