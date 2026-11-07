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
        // 1. Official Examination Centers (مراكز إجراء الامتحانات الرسمية - BEM / BAC / تقييم المكتسبات)
        Schema::create('official_exam_centers', function (Blueprint $table) {
            $table->id();
            $table->string('exam_type', 30)->default('bem')->index(); // bem, bac, primary_eval
            $table->unsignedSmallInteger('session_year')->default(2026)->index();
            $table->string('center_code', 50)->unique(); // CEN-BEM-2026-001
            $table->string('center_name'); // اسم مركز الإجراء
            $table->foreignId('head_user_id')->nullable()->constrained('users')->nullOnDelete(); // رئيس المركز
            $table->string('vice_head_name')->nullable(); // نائب رئيس المركز
            $table->string('secretariat_chief_name')->nullable(); // رئيس الأمانة
            $table->unsignedInteger('candidate_count')->default(0); // عدد المترشحين
            $table->unsignedSmallInteger('room_count')->default(0); // عدد القاعات
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('planning')->index(); // planning, active, completed
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Official Examination Rooms (قاعات الامتحان وتوزيع أرقام تسجيل المترشحين)
        Schema::create('official_exam_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('official_exam_centers')->cascadeOnDelete();
            $table->unsignedSmallInteger('room_number')->index();
            $table->string('room_label'); // القاعة رقم 01
            $table->string('physical_location')->nullable(); // الجناح الأول - الطابق العلوي
            $table->string('candidate_start_number', 50)->nullable(); // من رقم التسجيل
            $table->string('candidate_end_number', 50)->nullable(); // إلى رقم التسجيل
            $table->unsignedSmallInteger('candidate_count')->default(20);
            $table->unsignedSmallInteger('supervisors_required')->default(3);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['center_id', 'room_number']);
        });

        // 3. Official Exam Supervisors & Proctoring Roster (تأطير الحراسة والأمانة بالمركز)
        Schema::create('official_exam_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('official_exam_centers')->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('official_exam_rooms')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('supervisor_name');
            $table->string('origin_institution')->nullable(); // المؤسسة الأصلية
            $table->string('duty_role', 40)->default('room_supervisor')->index(); // room_chief, room_supervisor, corridor_guard, secretariat, reserve
            $table->string('attendance_status', 30)->default('present')->index(); // present, absent, replaced
            $table->string('replacement_name')->nullable();
            $table->string('convocation_reference', 50)->nullable(); // رقم الاستدعاء
            $table->timestamps();
        });

        // 4. Official Exam Incidents & Minutes (محاضر فتح الأظرفة والغش والوقائع - المرسوم 20-241)
        Schema::create('official_exam_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('official_exam_centers')->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('official_exam_rooms')->nullOnDelete();
            $table->string('incident_number', 50)->unique(); // INC/BEM/2026/001
            $table->string('incident_type', 40)->default('envelope_opening')->index(); // envelope_opening, cheating_attempt, candidate_absence, medical_incident, disciplinary_exclusion
            $table->date('incident_date')->index();
            $table->string('session_time', 20)->default('morning'); // morning, afternoon
            $table->string('subject_name'); // مادة الامتحان
            $table->string('candidate_registration_number', 50)->nullable();
            $table->string('candidate_name')->nullable();
            $table->text('description'); // حيثيات الواقعة
            $table->text('seized_evidence')->nullable(); // وسائل الغش المحجوزة
            $table->text('measures_taken')->nullable(); // الإجراءات المتخذة فورا
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('official_exam_incidents');
        Schema::dropIfExists('official_exam_supervisors');
        Schema::dropIfExists('official_exam_rooms');
        Schema::dropIfExists('official_exam_centers');
    }
};
