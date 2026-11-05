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
        Schema::table('students', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('last_name'); // male, female
            $table->string('regime')->nullable()->after('is_active'); // external, half_board, boarder
            $table->string('guardian_name')->nullable()->after('regime');
            $table->string('guardian_phone')->nullable()->after('guardian_name');
            $table->string('guardian_relation')->nullable()->after('guardian_phone'); // father, mother, tutor
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'regime',
                'guardian_name',
                'guardian_phone',
                'guardian_relation',
            ]);
        });
    }
};

