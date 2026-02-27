<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE accounts MODIFY COLUMN `status` ENUM('pending','active','suspended','closed') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE accounts MODIFY COLUMN `status` ENUM('active','suspended','closed') DEFAULT 'active'");
    }
};
