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
        // 1. School Budget Lines (أبواب ومواد الميزانية المدرسية - المرسوم التنفيذي 91-311)
        Schema::create('school_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->default('2026/2027')->index();
            $table->unsignedSmallInteger('fiscal_year')->default(2026)->index();
            $table->string('budget_type', 30)->default('expenditure')->index(); // expenditure (نفقات), revenue (إيرادات)
            $table->string('chapter', 50)->index(); // الباب
            $table->string('article'); // المادة
            $table->decimal('allocated_amount', 14, 2)->default(0.00); // الاعتماد المالي المخصص دج
            $table->decimal('committed_amount', 14, 2)->default(0.00); // الاعتمادات الملتزم بها
            $table->decimal('liquidated_amount', 14, 2)->default(0.00); // المبالغ المصفاة والمدفوعة
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year', 'budget_type', 'chapter', 'article'], 'budget_line_unique');
        });

        // 2. Budget Commitments & Purchase Orders (سندات الطلب وسجل الالتزامات - القانون 90-21)
        Schema::create('budget_commitments', function (Blueprint $table) {
            $table->id();
            $table->string('commitment_number', 50)->unique(); // ENG/2026/001
            $table->foreignId('budget_line_id')->constrained('school_budget_lines')->cascadeOnDelete();
            $table->string('supplier_name'); // اسم المتعامل / المورد
            $table->string('supplier_tax_number')->nullable(); // NIF / NIS
            $table->string('order_reference', 50)->unique(); // BC/2026/001
            $table->date('order_date')->index();
            $table->string('subject'); // موضوع الطلبية والتجهيز
            $table->decimal('estimated_amount', 12, 2)->default(0.00);
            $table->string('status', 30)->default('committed')->index(); // committed, liquidated, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Payment Mandates & Liquidations (حوالات الدفع وتصفية النفقات)
        Schema::create('payment_mandates', function (Blueprint $table) {
            $table->id();
            $table->string('mandate_number', 50)->unique(); // MAND/2026/001
            $table->foreignId('commitment_id')->constrained('budget_commitments')->cascadeOnDelete();
            $table->date('mandate_date')->index();
            $table->string('invoice_number'); // رقم الفاتورة
            $table->date('invoice_date'); // تاريخ الفاتورة
            $table->string('reception_slip_number')->nullable(); // رقم وصل الاستلام
            $table->decimal('liquidated_amount', 12, 2)->default(0.00); // المبلغ المصفى
            $table->string('payment_method', 40)->default('treasury_transfer')->index(); // treasury_transfer, cheque, cash
            $table->string('payment_reference')->nullable(); // رقم الشيك أو صك الخزينة
            $table->string('status', 30)->default('approved')->index(); // pending, approved, paid, rejected
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete(); // الآمر بالصرف (المدير)
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete(); // المحاسب العمومي (المقتصد)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_mandates');
        Schema::dropIfExists('budget_commitments');
        Schema::dropIfExists('school_budget_lines');
    }
};
