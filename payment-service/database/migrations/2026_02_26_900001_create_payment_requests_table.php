<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('request_ref')->unique();          // Our internal reference (PAY-XXXXXX)
            $table->string('external_ref')->nullable();        // Merchant's external reference
            $table->string('operator_ref')->nullable();        // Operator's reference/receipt
            $table->string('gateway_id')->nullable();          // Operator's gatewayId
            $table->enum('type', ['collection', 'disbursement']);
            $table->string('phone');                           // Customer phone number
            $table->decimal('amount', 15, 2);
            $table->decimal('platform_charge', 15, 2)->default(0);
            $table->decimal('operator_charge', 15, 2)->default(0);
            $table->string('currency', 10)->default('TZS');
            $table->string('operator_code');                   // mpesa, tigopesa, airtel, halopesa
            $table->string('operator_name');                   // M-Pesa, Tigo Pesa, etc.
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'timeout'])->default('pending');
            $table->string('description')->nullable();         // Payment description
            $table->json('operator_request')->nullable();      // What we sent to operator
            $table->json('operator_response')->nullable();     // What operator replied
            $table->json('callback_data')->nullable();         // Full callback data from operator
            $table->string('error_message')->nullable();       // Error details
            $table->enum('callback_status', ['pending', 'sent', 'failed'])->default('pending');
            $table->unsignedSmallInteger('callback_attempts')->default(0);
            $table->timestamp('callback_sent_at')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable(); // Linked transaction record
            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['operator_code', 'status']);
            $table->index(['phone']);
            $table->index('operator_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
