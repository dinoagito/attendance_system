<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('student_attendances', 'confirmed_status')) {
                $table->enum('confirmed_status', ['pending', 'approved', 'rejected'])->default('pending')->after('status');
            }
            if (!Schema::hasColumn('student_attendances', 'confirmed_by')) {
                $table->unsignedBigInteger('confirmed_by')->nullable()->after('confirmed_status');
            }
            if (!Schema::hasColumn('student_attendances', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
            }
            if (!Schema::hasColumn('student_attendances', 'rejection_reason')) {
                $table->string('rejection_reason')->nullable()->after('confirmed_at');
            }
            if (!Schema::hasColumn('student_attendances', 'approval_notes')) {
                $table->text('approval_notes')->nullable()->after('rejection_reason');
            }
        });

        // Set all existing records to pending if not already set
        DB::table('student_attendances')
            ->whereNull('confirmed_status')
            ->update(['confirmed_status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'confirmed_status',
                'confirmed_by',
                'confirmed_at',
                'rejection_reason',
                'approval_notes',
            ]);
        });
    }
};
