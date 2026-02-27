<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('business_type')->nullable()->after('business_name');
            $table->string('registration_number')->nullable()->after('business_type');
            $table->string('tin_number')->nullable()->after('registration_number');
            $table->string('address')->nullable()->after('tin_number');
            $table->string('city')->nullable()->after('address');
            $table->string('country')->default('Tanzania')->after('city');
            $table->string('id_type')->nullable()->after('country'); // national_id, passport, drivers_license
            $table->string('id_number')->nullable()->after('id_type');
            $table->string('id_document_url')->nullable()->after('id_number');
            $table->string('business_license_url')->nullable()->after('id_document_url');
            $table->text('kyc_notes')->nullable()->after('business_license_url');
            $table->timestamp('kyc_submitted_at')->nullable()->after('kyc_notes');
            $table->timestamp('kyc_approved_at')->nullable()->after('kyc_submitted_at');
            $table->unsignedBigInteger('kyc_approved_by')->nullable()->after('kyc_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'business_type', 'registration_number', 'tin_number',
                'address', 'city', 'country',
                'id_type', 'id_number', 'id_document_url', 'business_license_url',
                'kyc_notes', 'kyc_submitted_at', 'kyc_approved_at', 'kyc_approved_by',
            ]);
        });
    }
};
