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
        Schema::table('employee_schedules', function (Blueprint $table) {
            // Drop the foreign key constraint for employee_id
            $table->dropForeign(['employee_id']);
            // Make employee_id nullable
            $table->foreignId('employee_id')->nullable()->change()->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->foreignId('employee_id')->constrained()->onDelete('cascade')->change();
        });
    }
};
