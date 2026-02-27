<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change enum type to new values
        DB::statement("ALTER TABLE transactions MODIFY COLUMN `type` ENUM('collection','disbursement','topup','settlement') DEFAULT 'collection'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN `type` ENUM('deposit','withdrawal','payment','transfer','refund') DEFAULT 'payment'");
    }
};
