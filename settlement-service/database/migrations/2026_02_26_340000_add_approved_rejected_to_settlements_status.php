<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE settlements MODIFY COLUMN status ENUM('pending','approved','rejected','processing','completed','failed','cancelled') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE settlements MODIFY COLUMN status ENUM('pending','processing','completed','failed','cancelled') DEFAULT 'pending'");
    }
};
