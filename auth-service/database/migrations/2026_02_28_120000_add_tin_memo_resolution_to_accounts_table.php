<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('tin_certificate_url')->nullable()->after('tax_clearance_url');
            $table->string('company_memorandum_url')->nullable()->after('tin_certificate_url');
            $table->string('company_resolution_url')->nullable()->after('company_memorandum_url');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['tin_certificate_url', 'company_memorandum_url', 'company_resolution_url']);
        });
    }
};
