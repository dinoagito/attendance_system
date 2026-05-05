<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the employee_fingerprints table to store fingerprint templates
     * for biometric attendance verification using ZKTeco ZK9500 scanner.
     */
    public function up(): void
    {
        Schema::create('employee_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->longText('fingerprint_template'); // Base64 encoded fingerprint template
            $table->tinyInteger('finger_index')->default(0); // 0-9 for different fingers
            $table->integer('quality_score')->default(0); // Quality score 0-100
            $table->boolean('is_active')->default(true); // Soft delete flag
            $table->timestamps();
            
            // Index for faster lookups
            $table->index(['employee_id', 'finger_index']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_fingerprints');
    }
};
