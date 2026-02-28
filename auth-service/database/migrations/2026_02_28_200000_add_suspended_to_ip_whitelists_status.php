<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ip_whitelists MODIFY COLUMN status ENUM('pending','approved','rejected','suspended') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ip_whitelists MODIFY COLUMN status ENUM('pending','approved','rejected') DEFAULT 'pending'");
    }
};
