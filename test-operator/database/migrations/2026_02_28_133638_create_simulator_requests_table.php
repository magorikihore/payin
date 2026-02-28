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
        Schema::create('simulator_requests', function (Blueprint $table) {
            $table->id();

            // Request details from Payin
            $table->string('type');              // collection or disbursement
            $table->string('command');            // UssdPush or Disbursement
            $table->string('reference');          // PAY-xxx from Payin
            $table->string('transaction_id')->nullable();
            $table->string('msisdn');             // phone number
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('TZS');
            $table->string('callback_url');       // where to send callback

            // Auth header received
            $table->string('sp_id')->nullable();
            $table->string('merchant_code')->nullable();
            $table->boolean('auth_valid')->default(false);

            // Operator response (what we sent back immediately)
            $table->unsignedBigInteger('gateway_id')->nullable();
            $table->string('response_code')->default('0');

            // Callback status
            $table->string('callback_status')->default('pending'); // pending, sent, failed
            $table->string('callback_result')->nullable();          // success, failed
            $table->string('receipt_number')->nullable();
            $table->timestamp('callback_sent_at')->nullable();
            $table->text('callback_response')->nullable();

            // Raw request/response data
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulator_requests');
    }
};
