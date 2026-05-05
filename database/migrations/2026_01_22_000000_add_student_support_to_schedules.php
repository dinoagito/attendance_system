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
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('cascade')->after('employee_id');
            $table->string('name')->nullable()->after('student_id');
            $table->enum('user_type', ['employee', 'student'])->default('employee')->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->dropForeignKey(['student_id']);
            $table->dropColumn('student_id');
            $table->dropColumn('name');
            $table->dropColumn('user_type');
        });
    }
};
