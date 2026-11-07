<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Conforms to Algerian Ministry of National Education regulations on boarding (Internat),
     * dormitories, night study supervision, weekend leave, and student school bus transportation.
     */
    public function up(): void
    {
        // 1. Dormitory Rooms / Pavilions (أجنحة ومراقد الإيواء بالنظام الداخلي)
        Schema::create('dormitory_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('wing', 20)->default('boys'); // boys, girls
            $table->string('room_code', 30)->unique();
            $table->string('room_name');
            $table->unsignedSmallInteger('floor_level')->default(1);
            $table->unsignedSmallInteger('bed_capacity')->default(10);
            $table->unsignedSmallInteger('active_occupants_count')->default(0);
            $table->string('supervisor_in_charge')->nullable();
            $table->string('status', 20)->default('active'); // active, maintenance, closed
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Student Dormitory Bed Allocations (توزيع نزلاء الداخلي على الأسرّة والخزانات الفردية)
        Schema::create('student_dormitory_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dormitory_room_id')->constrained('dormitory_rooms')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('academic_year', 20)->index();
            $table->string('bed_number', 20); // LIT-01, etc.
            $table->string('locker_number', 20)->nullable(); // CAS-01, etc.
            $table->date('check_in_date');
            $table->date('check_out_date')->nullable();
            $table->string('status', 20)->default('active'); // active, suspended, transferred, vacated
            $table->boolean('linen_received')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Boarding Night Sessions & Study Permanences (سجل المداومة والمذاكرة الليلية وحراسة المراقد)
        Schema::create('boarding_night_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->index();
            $table->date('session_date');
            $table->string('session_type', 30)->default('evening_study'); // evening_study, night_supervision, weekend_duty
            $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('supervisor_name');
            $table->unsignedSmallInteger('boarders_present_count')->default(0);
            $table->unsignedSmallInteger('boarders_absent_count')->default(0);
            $table->unsignedSmallInteger('sickbay_count')->default(0);
            $table->unsignedSmallInteger('authorized_leave_count')->default(0);
            $table->text('incident_report')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // 4. Boarding Exit & Weekend Passes (رخص وبطاقات الخروج الأسبوعي والعطل للتلاميذ الداخليين)
        Schema::create('boarding_exit_passes', function (Blueprint $table) {
            $table->id();
            $table->string('pass_number', 50)->unique();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('academic_year', 20)->index();
            $table->string('pass_type', 30)->default('weekend_leave'); // weekend_leave, holiday_leave, exceptional_leave, medical_leave
            $table->dateTime('departure_datetime');
            $table->dateTime('expected_return_datetime');
            $table->dateTime('actual_return_datetime')->nullable();
            $table->string('escort_guardian_name');
            $table->string('destination');
            $table->string('status', 20)->default('authorized'); // authorized, departed, returned, overdue, cancelled
            $table->string('supervisor_signature_notes')->nullable();
            $table->foreignId('authorized_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // 5. School Bus Routes (خطوط وحافلات النقل المدرسي بالتنسيق مع البلديات)
        Schema::create('school_bus_routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_code', 30)->unique();
            $table->string('route_name');
            $table->string('bus_license_plate', 40)->nullable();
            $table->string('provider_entity')->default('بلدية المؤسسة');
            $table->string('driver_name');
            $table->string('driver_phone', 30)->nullable();
            $table->string('chaperone_name')->nullable();
            $table->unsignedSmallInteger('passenger_capacity')->default(30);
            $table->unsignedSmallInteger('subscribed_students_count')->default(0);
            $table->string('morning_departure_time', 10)->nullable();
            $table->string('evening_return_time', 10)->nullable();
            $table->text('stops_list');
            $table->string('status', 20)->default('active'); // active, suspended, inactive
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Student Bus Subscriptions & Cards (بطاقات اشتراك وركوب النقل المدرسي)
        Schema::create('student_bus_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_route_id')->constrained('school_bus_routes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('academic_year', 20)->index();
            $table->string('card_number', 50)->unique();
            $table->string('pickup_stop');
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('status', 20)->default('active'); // active, suspended, expired
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['bus_route_id', 'student_id', 'academic_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_bus_subscriptions');
        Schema::dropIfExists('school_bus_routes');
        Schema::dropIfExists('boarding_exit_passes');
        Schema::dropIfExists('boarding_night_sessions');
        Schema::dropIfExists('student_dormitory_allocations');
        Schema::dropIfExists('dormitory_rooms');
    }
};
