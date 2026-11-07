<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // محاضر التنصيب - Staff Installation Minutes
        Schema::create('staff_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('installation_date');
            $table->string('recruitment_order_ref')->nullable();      // رقم قرار التعيين
            $table->string('role_label')->nullable();                  // الصفة / الوظيفة
            $table->string('statutory_grade')->nullable();             // الرتبة القانونية
            $table->string('previous_institution')->nullable();        // المؤسسة السابقة
            $table->string('ministry_decision_ref')->nullable();       // رقم المقرر الوزاري
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // محاضر تسليم واستلام المهام - Official Handover / Takeover Minutes
        Schema::create('administrative_handovers', function (Blueprint $table) {
            $table->id();
            $table->string('role');                                    // HandoverRole enum value
            $table->date('handover_date');
            $table->foreignId('outgoing_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('incoming_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('outgoing_name')->nullable();               // للحفظ إن غادر المنظومة
            $table->string('incoming_name')->nullable();
            $table->text('inventory_summary')->nullable();             // ملخص الجرد
            $table->string('inventory_ref')->nullable();               // مرجع محضر الجرد
            $table->text('pending_dossiers')->nullable();              // الملفات المعلقة
            $table->text('remarks')->nullable();
            $table->boolean('outgoing_signed')->default(false);
            $table->boolean('incoming_signed')->default(false);
            $table->boolean('director_signed')->default(false);        // توقيع المدير / الرئيس
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // سجل البريد الإداري الصادر والوارد - Official Mail Registry
        Schema::create('official_mails', function (Blueprint $table) {
            $table->id();
            $table->string('mail_number')->unique();                   // ARR/2026/001 | DEP/2026/001
            $table->string('mail_type');                              // OfficialMailType: incoming | outgoing
            $table->date('mail_date');                                // تاريخ الوصول / الإرسال
            $table->string('sender_name')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('subject');                                // الموضوع
            $table->string('reference_number')->nullable();           // رقم مرجع المرسل
            $table->string('confidentiality')->default('normal');     // MailConfidentiality
            $table->string('attachment_path')->nullable();            // مسار الوثيقة الممسوحة
            $table->date('response_deadline')->nullable();            // أجل الرد
            $table->string('status')->default('pending');             // MailStatus: pending | processed | archived
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_mails');
        Schema::dropIfExists('administrative_handovers');
        Schema::dropIfExists('staff_installations');
    }
};
