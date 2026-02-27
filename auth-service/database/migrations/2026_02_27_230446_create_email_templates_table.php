<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // welcome, password_reset, kyc_approved, kyc_rejected
            $table->string('name');           // Display name
            $table->string('subject');
            $table->text('greeting');         // e.g. "Welcome, {{name}}!"
            $table->text('body');             // Main body with {{placeholders}}
            $table->string('action_text')->nullable();   // Button text
            $table->string('action_url')->nullable();    // Button URL
            $table->text('footer')->nullable();          // Salutation / footer
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
