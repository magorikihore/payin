<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // M-Pesa, Tigo Pesa, Airtel Money, Halopesa
            $table->string('code')->unique();               // mpesa, tigopesa, airtel, halopesa
            $table->string('api_url');                      // Operator API base URL
            $table->string('sp_id')->nullable();            // Service Provider ID
            $table->string('merchant_code')->nullable();    // Merchant Code
            $table->text('sp_password')->nullable();        // Service Provider Password (hashed key)
            $table->string('api_version', 10)->default('5.0'); // API version
            $table->string('collection_path')->nullable();  // e.g. /api/v1/ussd-push
            $table->string('disbursement_path')->nullable();// e.g. /api/v1/b2c
            $table->string('status_path')->nullable();      // e.g. /api/v1/status
            $table->string('callback_url')->nullable();     // Our callback URL shared with operator
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('extra_config')->nullable();       // Additional operator-specific config
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operators');
    }
};
