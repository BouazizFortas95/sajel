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
        Schema::create('educational_institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المؤسسة التربوية (مثل: متوسطة الشهيد العربي بن مهيدي)
            $table->string('code')->nullable()->unique(); // الرقم الميكانيكي / الرمز الوطني
            $table->string('stage')->default('middle'); // primary (ابتدائي), middle (متوسط), secondary (ثانوي), complex (مجمع)
            $table->string('directorate_name'); // مديرية التربية
            $table->string('wilaya')->nullable(); // الولاية
            $table->string('municipality')->nullable(); // البلدية / الدائرة
            $table->string('principal_name')->nullable(); // اسم مدير المؤسسة
            $table->string('counselor_name')->nullable(); // اسم مستشار التربية
            $table->string('phone')->nullable(); // رقم الهاتف الرسمي
            $table->string('email')->nullable(); // البريد الإلكتروني الرسمي
            $table->string('address')->nullable(); // العنوان الفعلي
            $table->unsignedInteger('capacity')->nullable(); // طاقة الاستيعاب النظرية (تلميذ)
            $table->string('regime')->default('half_board'); // externat (خارجي), half_board (نصف داخلي), internat (داخلي)
            $table->string('status')->default('active'); // active (نشطة), pending (قيد التجهيز), suspended (معلقة)
            $table->boolean('is_current')->default(false); // هل هي المؤسسة الحالية النشطة للنظام
            $table->string('academic_year')->default('2026-2027');
            $table->text('notes')->nullable(); // ملاحظات تقنية وإدارية للمطور
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_institutions');
    }
};
