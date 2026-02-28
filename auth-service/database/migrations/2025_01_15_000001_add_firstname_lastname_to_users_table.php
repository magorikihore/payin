<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('firstname')->after('id')->default('');
            $table->string('lastname')->after('firstname')->default('');
        });

        // Copy existing name into firstname for backward compatibility
        \DB::statement("UPDATE users SET firstname = name WHERE firstname = ''");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['firstname', 'lastname']);
        });
    }
};
