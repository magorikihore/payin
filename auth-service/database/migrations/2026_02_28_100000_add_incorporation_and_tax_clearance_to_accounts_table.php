<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('certificate_of_incorporation_url')->nullable()->after('business_license_url');
            $table->string('tax_clearance_url')->nullable()->after('certificate_of_incorporation_url');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['certificate_of_incorporation_url', 'tax_clearance_url']);
        });
    }
};
