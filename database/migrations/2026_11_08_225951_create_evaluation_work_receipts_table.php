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
        Schema::create('evaluation_work_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('academic_year', 20)->default('2026-2027');

            // 1. اختبار كشف المستوى (Diagnostic / Level Assessment Test)
            $table->boolean('diagnostic_test_submitted')->default(false);
            $table->date('diagnostic_test_date')->nullable();

            // 2. الواجب المنزلي (Homework 1, 2, 3)
            $table->boolean('homework_1_submitted')->default(false);
            $table->date('homework_1_date')->nullable();
            $table->boolean('homework_2_submitted')->default(false);
            $table->date('homework_2_date')->nullable();
            $table->boolean('homework_3_submitted')->default(false);
            $table->date('homework_3_date')->nullable();

            // 3. الفروض المحروسة (Supervised Quizzes / Tests 1, 2, 3)
            $table->boolean('quiz_1_submitted')->default(false);
            $table->date('quiz_1_date')->nullable();
            $table->boolean('quiz_2_submitted')->default(false);
            $table->date('quiz_2_date')->nullable();
            $table->boolean('quiz_3_submitted')->default(false);
            $table->date('quiz_3_date')->nullable();

            // 4. الاختبار (Term Exams 1, 2, 3)
            $table->boolean('exam_1_submitted')->default(false);
            $table->date('exam_1_date')->nullable();
            $table->boolean('exam_2_submitted')->default(false);
            $table->date('exam_2_date')->nullable();
            $table->boolean('exam_3_submitted')->default(false);
            $table->date('exam_3_date')->nullable();

            // الملاحظات والتأشيرة الإدارية
            $table->text('notes')->nullable();
            $table->boolean('director_visa')->default(false);
            $table->timestamp('director_visa_at')->nullable();
            $table->boolean('censor_visa')->default(false);
            $table->timestamp('censor_visa_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'academic_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_work_receipts');
    }
};
