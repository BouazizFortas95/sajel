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
        Schema::create('pedagogical_plan_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('academic_year', 20)->default('2026-2027');

            // المخطط السنوي (Annual Plan)
            $table->boolean('annual_plan_submitted')->default(false);
            $table->date('annual_plan_date')->nullable();

            // المخططات والتدرجات الشهرية (من سبتمبر إلى ماي)
            $table->boolean('september_submitted')->default(false);
            $table->date('september_date')->nullable();

            $table->boolean('october_submitted')->default(false);
            $table->date('october_date')->nullable();

            $table->boolean('november_submitted')->default(false);
            $table->date('november_date')->nullable();

            $table->boolean('december_submitted')->default(false);
            $table->date('december_date')->nullable();

            $table->boolean('january_submitted')->default(false);
            $table->date('january_date')->nullable();

            $table->boolean('february_submitted')->default(false);
            $table->date('february_date')->nullable();

            $table->boolean('march_submitted')->default(false);
            $table->date('march_date')->nullable();

            $table->boolean('april_submitted')->default(false);
            $table->date('april_date')->nullable();

            $table->boolean('may_submitted')->default(false);
            $table->date('may_date')->nullable();

            // الملاحظات والتأشيرات الإدارية
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
        Schema::dropIfExists('pedagogical_plan_receipts');
    }
};
