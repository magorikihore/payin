<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->enum('wallet_type', ['collection', 'disbursement'])->after('operator')->default('collection');
            $table->dropUnique(['user_id', 'operator']);
            $table->unique(['user_id', 'operator', 'wallet_type']);
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'operator', 'wallet_type']);
            $table->dropColumn('wallet_type');
            $table->unique(['user_id', 'operator']);
        });
    }
};
