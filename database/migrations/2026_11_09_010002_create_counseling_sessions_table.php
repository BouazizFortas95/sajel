<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counseling_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete()->index();
            $table->foreignId('counselor_id')->constrained('users')->cascadeOnDelete()->index();
            $table->string('academic_year', 20)->index();
            $table->date('session_date')->index();
            $table->string('reason_category', 50)->index();
            $table->string('interviewee_type', 30)->default('student_only');
            $table->text('observations');
            $table->text('action_plan')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('status', 30)->default('open')->index();
            $table->boolean('is_confidential')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counseling_sessions');
    }
};
