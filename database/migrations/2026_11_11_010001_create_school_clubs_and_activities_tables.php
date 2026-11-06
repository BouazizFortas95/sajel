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
        Schema::create('school_clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('type')->default('scientific');
            $table->string('academic_year', 10)->default('2026-2027');
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->foreignId('lead_teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assistant_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('meeting_day', 20)->nullable();
            $table->string('meeting_time', 50)->nullable();
            $table->string('meeting_location', 100)->nullable();
            $table->unsignedSmallInteger('max_members')->default(30);
            $table->string('status', 20)->default('active');
            $table->string('color', 30)->nullable();
            $table->timestamps();

            $table->index(['academic_year', 'status']);
            $table->index('type');
        });

        Schema::create('club_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_club_id')->constrained('school_clubs')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('role', 30)->default('active_member');
            $table->date('joined_at');
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_club_id', 'student_id']);
            $table->index(['school_club_id', 'role']);
        });

        Schema::create('club_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_club_id')->constrained('school_clubs')->cascadeOnDelete();
            $table->string('title');
            $table->string('activity_type', 30)->default('workshop');
            $table->date('activity_date');
            $table->string('start_time', 20)->nullable();
            $table->string('end_time', 20)->nullable();
            $table->string('location', 100)->nullable();
            $table->text('objectives')->nullable();
            $table->text('summary')->nullable();
            $table->unsignedSmallInteger('attendees_count')->default(0);
            $table->boolean('is_public')->default(false);
            $table->string('status', 20)->default('completed');
            $table->text('supervisor_notes')->nullable();
            $table->timestamps();

            $table->index(['school_club_id', 'activity_date']);
            $table->index('activity_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_activities');
        Schema::dropIfExists('club_memberships');
        Schema::dropIfExists('school_clubs');
    }
};

