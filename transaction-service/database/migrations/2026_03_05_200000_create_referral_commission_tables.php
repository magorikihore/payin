<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Commission rules — how much the referrer earns per transaction
        Schema::create('referral_commission_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                          // e.g. "Airtel Collection 5%"
            $table->string('operator', 50)->default('all');                  // operator code or 'all'
            $table->string('transaction_type', 30)->default('all');          // collection, disbursement, or 'all'
            $table->enum('commission_type', ['fixed', 'percentage', 'dynamic'])->default('percentage');
            $table->decimal('commission_value', 15, 4)->default(0);         // flat amount or percentage
            $table->json('tiers')->nullable();                               // for dynamic: [{min_amount, max_amount, commission_type, commission_value}]
            $table->decimal('min_amount', 15, 2)->default(0);               // minimum txn amount to qualify
            $table->decimal('max_amount', 15, 2)->default(0);               // 0 = no upper limit
            $table->decimal('max_commission', 15, 2)->default(0);           // 0 = no cap per transaction
            $table->unsignedBigInteger('referrer_account_id')->nullable();  // NULL = applies to all referrers
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('referrer_account_id');
            $table->index('status');
        });

        // Earned commissions log
        Schema::create('referral_earnings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_account_id');              // the agent who referred
            $table->unsignedBigInteger('referred_account_id');              // the client who transacted
            $table->string('transaction_ref', 50);                          // linked transaction reference
            $table->decimal('transaction_amount', 15, 2);                   // original txn amount
            $table->string('operator', 50)->nullable();
            $table->string('transaction_type', 30);                         // collection or disbursement
            $table->enum('commission_type', ['fixed', 'percentage', 'dynamic']);
            $table->decimal('commission_rate', 15, 4);                      // the rate/value applied
            $table->decimal('commission_amount', 15, 2);                    // actual earned amount
            $table->enum('status', ['pending', 'credited', 'failed'])->default('pending');
            $table->string('wallet_reference')->nullable();                 // wallet credit txn ref
            $table->timestamps();

            $table->index('referrer_account_id');
            $table->index('referred_account_id');
            $table->index('transaction_ref');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_earnings');
        Schema::dropIfExists('referral_commission_configs');
    }
};
