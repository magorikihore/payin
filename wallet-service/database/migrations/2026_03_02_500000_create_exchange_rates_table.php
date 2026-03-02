<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Exchange rates managed by admin
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 10);          // e.g. TZS
            $table->string('to_currency', 10);            // e.g. KES
            $table->decimal('buy_rate', 18, 6);           // how much 1 unit of to_currency costs in from_currency
            $table->decimal('sell_rate', 18, 6);          // how much 1 unit of from_currency yields in to_currency
            $table->decimal('conversion_fee_percent', 5, 2)->default(2.00); // platform fee %
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency']);
        });

        // Log of every currency exchange transaction
        Schema::create('currency_exchanges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('reference', 50)->unique();
            $table->string('from_currency', 10);
            $table->string('to_currency', 10);
            $table->decimal('from_amount', 15, 2);        // amount debited from source wallet
            $table->decimal('to_amount', 15, 2);          // amount credited to destination wallet
            $table->decimal('rate_applied', 18, 6);       // the rate used
            $table->decimal('fee_percent', 5, 2);         // fee % at time of exchange
            $table->decimal('fee_amount', 15, 2);         // actual fee in from_currency
            $table->decimal('platform_revenue', 15, 2);   // revenue earned by platform
            $table->string('from_operator', 50);          // source wallet operator
            $table->string('to_operator', 50);            // destination wallet operator
            $table->enum('from_wallet_type', ['collection', 'disbursement'])->default('collection');
            $table->enum('to_wallet_type', ['collection', 'disbursement'])->default('collection');
            $table->enum('status', ['completed', 'failed', 'reversed'])->default('completed');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_exchanges');
        Schema::dropIfExists('exchange_rates');
    }
};
