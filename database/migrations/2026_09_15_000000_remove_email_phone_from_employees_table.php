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
        // Clear existing email and phone values before dropping columns
        if (Schema::hasTable('employees')) {
            if (Schema::hasColumn('employees', 'email') && Schema::hasColumn('employees', 'phone')) {
                DB::table('employees')->update(['email' => null, 'phone' => null]);
            } elseif (Schema::hasColumn('employees', 'email')) {
                DB::table('employees')->update(['email' => null]);
            } elseif (Schema::hasColumn('employees', 'phone')) {
                DB::table('employees')->update(['phone' => null]);
            }

            Schema::table('employees', function (Blueprint $table) {
                if (Schema::hasColumn('employees', 'email')) {
                    $table->dropColumn('email');
                }
                if (Schema::hasColumn('employees', 'phone')) {
                    $table->dropColumn('phone');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (!Schema::hasColumn('employees', 'email')) {
                    $table->string('email')->unique()->nullable()->after('department');
                }
                if (!Schema::hasColumn('employees', 'phone')) {
                    $table->string('phone')->nullable()->after('email');
                }
            });
        }
    }
};
