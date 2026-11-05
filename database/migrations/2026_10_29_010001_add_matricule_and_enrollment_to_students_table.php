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
            $table->string('matricule_number')->nullable()->after('registration_number');
            $table->string('place_of_birth')->nullable()->after('date_of_birth');
            $table->string('national_id_number')->nullable()->after('matricule_number');
            $table->date('enrollment_date')->nullable()->after('group_id');
            $table->date('departure_date')->nullable()->after('enrollment_date');
            $table->boolean('is_active')->default(true)->after('departure_date');

            $table->index('matricule_number');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['matricule_number']);
            $table->dropIndex(['is_active']);
            $table->dropColumn([
                'matricule_number',
                'place_of_birth',
                'national_id_number',
                'enrollment_date',
                'departure_date',
                'is_active',
            ]);
        });
    }
};
