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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_request_id');
            $table->unsignedBigInteger('account_id');
            $table->string('url', 500);
            $table->json('request_payload');
            $table->smallInteger('http_status')->nullable();
            $table->text('response_body')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->string('status', 20)->default('pending'); // success, failed, timeout, error
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('attempt_number')->default(1);
            $table->timestamps();

            $table->index('payment_request_id');
            $table->index('account_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
