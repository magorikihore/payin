<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Drop old unique index that doesn't include currency
            $table->dropUnique(['user_id', 'operator', 'wallet_type']);

            // Create new unique index that includes currency for multi-currency support
            // Using account_id to match the firstOrCreate logic in WalletController
            $table->unique(['account_id', 'operator', 'wallet_type', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique(['account_id', 'operator', 'wallet_type', 'currency']);
            $table->unique(['user_id', 'operator', 'wallet_type']);
        });
    }
};
