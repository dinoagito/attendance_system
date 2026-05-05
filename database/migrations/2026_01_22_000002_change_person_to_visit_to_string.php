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
        Schema::table('visitors', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['person_to_visit']);
            // Change person_to_visit from unsignedBigInteger to string
            $table->dropColumn('person_to_visit');
            $table->string('person_to_visit', 100)->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn('person_to_visit');
            $table->unsignedBigInteger('person_to_visit')->nullable()->after('phone');
            $table->foreign('person_to_visit')->references('id')->on('employees')->onDelete('set null');
        });
    }
};
