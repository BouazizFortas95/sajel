<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_counseling_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->string('family_status')->default('both_parents');
            $table->unsignedSmallInteger('siblings_count')->default(0);
            $table->unsignedSmallInteger('birth_order')->default(1);
            $table->string('father_job')->nullable();
            $table->string('mother_job')->nullable();
            $table->boolean('social_grant')->default(false);
            $table->string('blood_type', 10)->nullable();
            $table->json('chronic_conditions')->nullable();
            $table->boolean('has_physical_disability')->default(false);
            $table->boolean('pe_exemption')->default(false);
            $table->text('health_notes')->nullable();
            $table->string('primary_school_name')->nullable();
            $table->string('primary_graduation_year', 20)->nullable();
            $table->unsignedSmallInteger('repeated_years_count')->default(0);
            $table->json('repeated_levels')->nullable();
            $table->json('learning_difficulties')->nullable();
            $table->json('behavior_tendencies')->nullable();
            $table->json('interests_and_talents')->nullable();
            $table->string('career_aspirations')->nullable();
            $table->text('counselor_general_notes')->nullable();
            $table->boolean('is_confidential')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_counseling_profiles');
    }
};
