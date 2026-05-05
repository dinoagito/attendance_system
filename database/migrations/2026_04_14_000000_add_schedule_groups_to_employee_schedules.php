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
            $table->json('schedule_days')->nullable()->after('day_of_week');
            $table->string('schedule_group_key')->nullable()->unique()->after('schedule_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->dropUnique(['schedule_group_key']);
            $table->dropColumn(['schedule_days', 'schedule_group_key']);
        });
    }
};