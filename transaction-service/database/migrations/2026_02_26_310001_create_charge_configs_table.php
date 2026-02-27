<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('operator')->default('all'); // M-Pesa, Tigo Pesa, Airtel Money, Halopesa, all
            $table->string('transaction_type')->default('all'); // collection, disbursement, topup, settlement, all
            $table->enum('charge_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('charge_value', 15, 4)->default(0);
            $table->decimal('min_amount', 15, 2)->default(0);
            $table->decimal('max_amount', 15, 2)->default(0); // 0 = no max
            $table->enum('applies_to', ['platform', 'operator'])->default('platform');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_configs');
    }
};
