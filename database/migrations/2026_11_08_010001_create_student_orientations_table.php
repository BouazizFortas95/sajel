<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_orientations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('academic_year', 20)->index();

            // Wishes Card (Fiche de Vœux)
            $table->string('first_choice', 50)->nullable();
            $table->string('second_choice', 50)->nullable();
            $table->boolean('guardian_approved')->default(false);
            $table->text('guardian_notes')->nullable();

            // Trimester 2 Pre-Orientation Council (مجلس التوجيه المسبق)
            $table->string('pre_orientation_choice', 50)->nullable();
            $table->text('pre_orientation_notes')->nullable();

            // Pedagogical Profile Scores & Examination Results
            $table->decimal('scientific_profile_score', 4, 2)->nullable();
            $table->decimal('literary_profile_score', 4, 2)->nullable();
            $table->decimal('annual_general_average', 4, 2)->nullable();
            $table->decimal('bem_score', 4, 2)->nullable();
            $table->decimal('admission_average', 4, 2)->nullable();
            $table->boolean('is_admitted')->default(false);

            // Final Deliberation & Stream Assignment (مجلس القبول والتوجيه)
            $table->string('final_decision', 50)->nullable();
            $table->timestamp('deliberated_at')->nullable();
            $table->text('counselor_notes')->nullable();

            // Wilaya Appeal Commission (اللجنة الولائية للطعن)
            $table->string('appeal_status', 30)->default('none')->index();
            $table->string('appeal_decision', 50)->nullable();
            $table->text('appeal_notes')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_orientations');
    }
};
