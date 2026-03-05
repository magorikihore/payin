<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('referral_code', 20)->unique()->nullable()->after('status');
            $table->unsignedBigInteger('referred_by')->nullable()->after('referral_code');
            $table->timestamp('referred_at')->nullable()->after('referred_by');

            $table->index('referred_by');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['referred_by']);
            $table->dropColumn(['referral_code', 'referred_by', 'referred_at']);
        });
    }
};
