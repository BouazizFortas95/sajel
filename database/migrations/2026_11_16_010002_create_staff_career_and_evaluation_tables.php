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
        // 1. Annual Administrative and Pedagogical Evaluations (التنقيط والتقييم السنوي)
        Schema::create('staff_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('academic_year', 20)->index(); // e.g., '2025/2026'
            $table->unsignedSmallInteger('evaluation_year')->index(); // e.g., 2026
            $table->decimal('administrative_score', 4, 2)->default(0.00); // النقطة الإدارية الإجمالية /20
            $table->decimal('punctuality_score', 4, 2)->default(0.00); // المواظبة والانضباط /5
            $table->decimal('performance_score', 4, 2)->default(0.00); // النشاط والمردودية /5
            $table->decimal('conduct_score', 4, 2)->default(0.00); // السلوك والمظهر المهني /5
            $table->decimal('initiative_score', 4, 2)->default(0.00); // المبادرة والمساهمة /5
            $table->decimal('pedagogical_score', 4, 2)->nullable(); // نقطة التفتيش البيداغوجي /20
            $table->date('last_inspection_date')->nullable(); // تاريخ آخر زيارة تفتيش
            $table->string('appreciation', 50)->default('good'); // ممتاز، جيد جدا، جيد، مقبول، ضعيف
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete(); // مدير المؤسسة
            $table->text('evaluator_notes')->nullable(); // ملاحظات رئيس المؤسسة
            $table->date('evaluated_at')->nullable();
            $table->string('staff_signature_status', 30)->default('pending'); // pending, acknowledged, contested
            $table->text('staff_contestation_notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'academic_year'], 'user_academic_evaluation_unique');
        });

        // 2. Career Step Advancements (جدول الترقية في الدرجات وتحديد وتائر الدنيا والمتوسطة والقصوى)
        Schema::create('career_advancements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('current_step'); // الدرجة الحالية (0 إلى 11)
            $table->unsignedTinyInteger('next_step'); // الدرجة المقترحة (1 إلى 12)
            $table->date('current_step_date'); // تاريخ مفعول الدرجة السابقة
            $table->date('eligible_at_minimum'); // استحقاق الوتيرة الدنيا (سنتان ونصف)
            $table->date('eligible_at_medium'); // استحقاق الوتيرة المتوسطة (3 سنوات)
            $table->date('eligible_at_maximum'); // استحقاق الوتيرة القصوى (3 سنوات ونصف)
            $table->string('advancement_rhythm', 30)->default('medium'); // minimum, medium, maximum
            $table->date('effective_date')->nullable(); // تاريخ السريان المعتمد
            $table->date('financial_impact_date')->nullable(); // تاريخ الأثر المالي
            $table->string('status', 30)->default('draft'); // draft, submitted, approved_by_cap, rejected, archived
            $table->string('commission_reference')->nullable(); // رقم محضر وتاريخ اللجنة المتساوية الأعضاء
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // 3. Staff Movements (حركات تنقل الموظفين والاستيداع والانتداب والتقاعد)
        Schema::create('staff_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('movement_type', 40); // transfer, secondment, availability, retirement, integration, new_hire
            $table->string('status', 30)->default('pending'); // pending, approved, in_progress, completed, rejected
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->string('decision_number')->nullable();
            $table->date('decision_date')->nullable();
            $table->string('source_institution')->nullable();
            $table->string('target_institution')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'movement_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_movements');
        Schema::dropIfExists('career_advancements');
        Schema::dropIfExists('staff_evaluations');
    }
};

