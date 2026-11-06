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
        Schema::create('pedagogical_councils', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->index();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('council_type', 40)->default('subject_council')->index();
            $table->unsignedTinyInteger('session_term')->nullable()->index();
            $table->string('title');
            $table->dateTime('held_at')->index();
            $table->string('location')->nullable();
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('agenda')->nullable();
            $table->text('curriculum_pacing_notes')->nullable();
            $table->text('unified_assessments_agreement')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('pv_reference', 100)->nullable()->unique();
            $table->string('status', 30)->default('scheduled')->index();
            $table->timestamps();
        });

        Schema::create('pedagogical_council_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedagogical_council_id')->constrained('pedagogical_councils')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_present')->default(true);
            $table->string('role_in_council')->default('أستاذ عضو');
            $table->string('absence_justification')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['pedagogical_council_id', 'user_id'], 'council_user_unique');
        });

        Schema::create('pedagogical_seminars', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->index();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('title');
            $table->string('inspector_name')->nullable();
            $table->string('host_school')->nullable();
            $table->date('scheduled_at')->index();
            $table->string('seminar_type', 40)->default('inspectoral_seminar')->index();
            $table->string('target_teachers_description')->nullable();
            $table->foreignId('animator_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('objectives')->nullable();
            $table->text('recommendations_summary')->nullable();
            $table->string('official_call_number', 100)->nullable();
            $table->string('status', 30)->default('planned')->index();
            $table->timestamps();
        });

        Schema::create('pedagogical_seminar_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedagogical_seminar_id')->constrained('pedagogical_seminars')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('attended')->default(true);
            $table->string('certificate_ref')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['pedagogical_seminar_id', 'user_id'], 'seminar_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedagogical_seminar_attendances');
        Schema::dropIfExists('pedagogical_seminars');
        Schema::dropIfExists('pedagogical_council_attendances');
        Schema::dropIfExists('pedagogical_councils');
    }
};

