<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('inspector_name');
            $table->string('inspector_role')->default('inspector'); // inspector | principal
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->foreignId('daily_log_id')->nullable()->constrained('daily_logs')->nullOnDelete();
            $table->foreignId('curriculum_resource_id')->nullable()->constrained('curriculum_resources')->nullOnDelete();
            $table->date('visit_date');
            $table->string('lesson_topic');
            $table->json('evaluation_criteria')->nullable();
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->decimal('pedagogical_score', 4, 2)->nullable();
            $table->string('pedagogical_appreciation')->nullable();
            $table->date('inspector_signature_date')->nullable();
            $table->timestamp('teacher_acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'visit_date']);
            $table->index(['subject_id', 'classroom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_reports');
    }
};
