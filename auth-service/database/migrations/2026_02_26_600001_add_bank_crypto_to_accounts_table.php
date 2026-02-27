<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Bank settlement details
            $table->string('bank_name', 191)->nullable()->after('country');
            $table->string('bank_account_name', 191)->nullable()->after('bank_name');
            $table->string('bank_account_number', 191)->nullable()->after('bank_account_name');
            $table->string('bank_swift', 191)->nullable()->after('bank_account_number');
            $table->string('bank_branch', 191)->nullable()->after('bank_swift');

            // Crypto wallet settlement
            $table->string('crypto_wallet_address', 500)->nullable()->after('bank_branch');
            $table->string('crypto_network', 191)->nullable()->after('crypto_wallet_address');
            $table->string('crypto_currency', 191)->nullable()->after('crypto_network');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name', 'bank_account_name', 'bank_account_number',
                'bank_swift', 'bank_branch',
                'crypto_wallet_address', 'crypto_network', 'crypto_currency',
            ]);
        });
    }
};
