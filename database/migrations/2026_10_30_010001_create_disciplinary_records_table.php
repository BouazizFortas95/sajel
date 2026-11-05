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
        Schema::create('disciplinary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('academic_year')->default('2026/2027');
            $table->string('session_number')->nullable();
            $table->date('incident_date');
            $table->dateTime('session_date');
            $table->string('infraction_degree')->default('second_degree');
            $table->text('incident_description');
            $table->foreignId('reported_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('scheduled');
            $table->date('summons_sent_at')->nullable();
            $table->boolean('guardian_attended')->default(false);
            $table->text('student_statement')->nullable();
            $table->string('decision')->nullable();
            $table->integer('exclusion_days')->nullable();
            $table->date('exclusion_start_date')->nullable();
            $table->date('exclusion_end_date')->nullable();
            $table->decimal('compensation_amount', 8, 2)->nullable();
            $table->dateTime('deliberated_at')->nullable();
            $table->date('guardian_notified_at')->nullable();
            $table->json('attendees')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('president_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'academic_year']);
            $table->index('status');
            $table->index('decision');
            $table->index('incident_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disciplinary_records');
    }
};
