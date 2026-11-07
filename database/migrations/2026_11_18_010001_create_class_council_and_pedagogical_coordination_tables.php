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
        // 1. Class Council Sessions (جلسات مجالس الأقسام الرسمية - القرار الوزاري 158)
        Schema::create('class_council_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->unsignedTinyInteger('trimester')->default(1)->index(); // 1, 2, 3
            $table->string('academic_year', 20)->default('2026/2027')->index();
            $table->dateTime('session_date')->index();
            $table->foreignId('president_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('main_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('counselor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('student_delegates')->nullable();
            $table->text('general_appreciation')->nullable();
            $table->decimal('class_average', 4, 2)->nullable();
            $table->decimal('highest_average', 4, 2)->nullable();
            $table->decimal('lowest_average', 4, 2)->nullable();
            $table->decimal('success_rate', 5, 2)->nullable();
            $table->unsignedSmallInteger('total_students')->default(0);
            $table->string('status', 30)->default('draft')->index(); // draft, deliberated, certified
            $table->text('council_decisions_summary')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['classroom_id', 'trimester', 'academic_year'], 'class_council_trimester_unique');
        });

        // 2. Class Council Decisions (قرارات مجلس القسم الفردية - تشجيع، تهنئة، تنبيه، انتقال)
        Schema::create('class_council_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_council_session_id')->constrained('class_council_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('trimester_average', 4, 2)->nullable();
            $table->decimal('annual_average', 4, 2)->nullable();
            $table->unsignedSmallInteger('rank')->nullable();
            $table->string('honors', 40)->default('none')->index(); // none, tableau_honneur, encouragement, felicitations
            $table->string('sanctions', 40)->default('none')->index(); // none, avertissement_travail, blame_travail, avertissement_conduite, blame_conduite
            $table->string('end_of_year_decision', 40)->default('none')->index(); // none, admis, redouble, reoriente, exclu
            $table->string('orientation_stream')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['class_council_session_id', 'student_id'], 'session_student_unique');
        });

        // 3. Subject Coordination Protocols (محاضر التنسيق البيداغوجي لأساتذة المادة - القرار 157)
        Schema::create('subject_coordination_protocols', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->default('2026/2027')->index();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedTinyInteger('trimester')->default(1)->index();
            $table->date('meeting_date')->index();
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('attendees')->nullable();
            $table->string('topic');
            $table->text('syllabus_pacing_notes')->nullable();
            $table->text('unified_exams_notes')->nullable();
            $table->text('difficulties_and_remediations')->nullable();
            $table->text('recommendations')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Curriculum Pacing Audits (تدقيق نسب إنجاز وتدرج البرامج والمناهج الدراسية)
        Schema::create('curriculum_pacing_audits', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->default('2026/2027')->index();
            $table->unsignedTinyInteger('trimester')->default(1)->index();
            $table->date('audit_date')->index();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('expected_resources_count')->default(0);
            $table->unsignedSmallInteger('completed_resources_count')->default(0);
            $table->decimal('execution_percentage', 5, 2)->default(0.00);
            $table->string('pacing_status', 30)->default('on_track')->index(); // ahead, on_track, minor_delay, critical_delay
            $table->text('delay_reasons')->nullable();
            $table->text('compensatory_plan')->nullable();
            $table->foreignId('audited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['academic_year', 'trimester', 'classroom_id', 'subject_id'], 'pacing_audit_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_pacing_audits');
        Schema::dropIfExists('subject_coordination_protocols');
        Schema::dropIfExists('class_council_decisions');
        Schema::dropIfExists('class_council_sessions');
    }
};

