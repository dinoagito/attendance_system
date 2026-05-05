<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores WebAuthn credentials for Windows Hello biometric authentication
     */
    public function up(): void
    {
        Schema::create('employee_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('credential_id')->unique(); // WebAuthn credential ID
            $table->text('public_key'); // Public key for verification
            $table->string('device_name')->nullable(); // e.g., "Windows Hello"
            $table->integer('sign_count')->default(0); // For replay attack prevention
            $table->timestamps();
            
            $table->index('credential_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_credentials');
    }
};
