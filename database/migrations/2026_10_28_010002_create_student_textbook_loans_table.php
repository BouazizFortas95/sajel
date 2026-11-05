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
        Schema::create('student_textbook_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('textbook_id')->constrained('textbooks')->cascadeOnDelete();
            $table->string('academic_year')->default('2026/2027');
            $table->string('distribution_type')->default('loan');
            $table->date('distributed_at');
            $table->date('returned_at')->nullable();
            $table->string('return_condition')->nullable();
            $table->boolean('clearance_granted')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('distributed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'textbook_id', 'academic_year'], 'student_textbook_year_unique');
            $table->index(['student_id', 'academic_year']);
            $table->index('distribution_type');
            $table->index('clearance_granted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_textbook_loans');
    }
};

