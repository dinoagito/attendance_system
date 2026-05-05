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
        Schema::table('employee_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_attendances', 'time_in_2')) {
                $table->time('time_in_2')->nullable()->after('time_out');
            }
            if (!Schema::hasColumn('employee_attendances', 'time_out_2')) {
                $table->time('time_out_2')->nullable()->after('time_in_2');
            }
            if (!Schema::hasColumn('employee_attendances', 'time_in_3')) {
                $table->time('time_in_3')->nullable()->after('time_out_2');
            }
            if (!Schema::hasColumn('employee_attendances', 'time_out_3')) {
                $table->time('time_out_3')->nullable()->after('time_in_3');
            }
            if (!Schema::hasColumn('employee_attendances', 'time_in_4')) {
                $table->time('time_in_4')->nullable()->after('time_out_3');
            }
            if (!Schema::hasColumn('employee_attendances', 'time_out_4')) {
                $table->time('time_out_4')->nullable()->after('time_in_4');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_attendances', function (Blueprint $table) {
            $columns = [
                'time_in_2',
                'time_out_2',
                'time_in_3',
                'time_out_3',
                'time_in_4',
                'time_out_4',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('employee_attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
