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
        Schema::create('quarterly_institutional_reports', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 15);
            $table->unsignedTinyInteger('trimester')->default(1);
            $table->string('status', 20)->default('draft');

            // Academic performance metrics
            $table->decimal('general_success_rate', 5, 2)->default(0);
            $table->unsignedInteger('evaluated_students_count')->default(0);
            $table->unsignedInteger('passed_students_count')->default(0);
            $table->unsignedInteger('excellence_count')->default(0); // >= 16
            $table->unsignedInteger('encouragement_count')->default(0); // 14 - 15.99
            $table->unsignedInteger('honor_count')->default(0); // 12 - 13.99
            $table->unsignedInteger('passing_count')->default(0); // 10 - 11.99
            $table->unsignedInteger('failing_count')->default(0); // < 10

            // Attendance & session volume metrics
            $table->unsignedInteger('total_due_sessions')->default(0);
            $table->unsignedInteger('completed_sessions')->default(0);
            $table->unsignedInteger('teacher_absence_hours')->default(0);
            $table->unsignedInteger('teacher_substitution_hours')->default(0);
            $table->unsignedInteger('student_absence_records')->default(0);

            // Discipline & Counseling metrics
            $table->unsignedInteger('disciplinary_councils_count')->default(0);
            $table->unsignedInteger('counseling_sessions_count')->default(0);

            // Detailed breakdowns (JSON)
            $table->json('level_breakdown')->nullable();
            $table->json('subject_breakdown')->nullable();

            // Principal's qualitative notes & guidance
            $table->text('executive_summary')->nullable();
            $table->text('pedagogical_recommendations')->nullable();

            // Administrative validation & audit
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            $table->unique(['academic_year', 'trimester'], 'quarterly_reports_year_trimester_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quarterly_institutional_reports');
    }
};
