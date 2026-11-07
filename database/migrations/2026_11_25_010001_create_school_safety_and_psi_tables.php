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
        // 1. School Internal Emergency & Security Plan (PSI)
        Schema::create('safety_emergency_plans', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->index();
            $table->string('plan_code', 30)->unique();
            $table->string('title');
            $table->string('crisis_cell_leader');
            $table->string('evacuation_coordinator');
            $table->string('first_aid_leader')->nullable();
            $table->string('technical_safety_officer')->nullable();
            $table->json('assembly_points')->nullable();
            $table->json('emergency_contacts')->nullable();
            $table->json('hazardous_zones')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->date('approval_date')->nullable();
            $table->string('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Safety & First-Aid Intervention Teams
        Schema::create('safety_intervention_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safety_plan_id')->constrained('safety_emergency_plans')->cascadeOnDelete();
            $table->string('team_type', 40)->index();
            $table->string('member_name');
            $table->string('institution_role', 50)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assigned_zone')->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->boolean('is_certified_first_aider')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. School Accidents and Safety Incidents Ledger
        Schema::create('school_safety_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->index();
            $table->string('incident_number', 30)->unique();
            $table->string('incident_type', 40)->default('student_accident')->index();
            $table->string('severity_level', 30)->default('minor')->index();
            $table->dateTime('incident_date');
            $table->string('location');
            $table->string('victim_type', 30)->default('student')->index();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('victim_name');
            $table->text('circumstances_description');
            $table->text('bodily_injuries_description')->nullable();
            $table->text('immediate_care_administered')->nullable();
            $table->boolean('hospital_evacuation')->default(false);
            $table->string('hospital_name')->nullable();
            $table->string('medical_certificate_ref')->nullable();
            $table->unsignedInteger('temporary_incapacity_days')->default(0);
            $table->text('witnesses_statements')->nullable();
            $table->string('supervising_personnel_at_time')->nullable();
            $table->boolean('insurance_declared')->default(false)->index();
            $table->date('declaration_date')->nullable();
            $table->boolean('directorate_notified')->default(false);
            $table->string('status', 30)->default('in_treatment')->index();
            $table->string('investigating_officer_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Fire Safety & Gas Detection Equipment Register
        Schema::create('safety_fire_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_code', 40)->unique();
            $table->string('equipment_type', 40)->index();
            $table->string('exact_location');
            $table->date('installation_date')->nullable();
            $table->date('last_inspection_date');
            $table->date('next_inspection_due_date')->index();
            $table->string('inspection_status', 30)->default('operational')->index();
            $table->string('inspector_entity')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Emergency Disaster Evacuation Drills & Simulations
        Schema::create('safety_evacuation_drills', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->index();
            $table->string('drill_code', 30)->unique();
            $table->date('drill_date');
            $table->string('drill_time', 20);
            $table->string('simulation_scenario', 40)->default('earthquake')->index();
            $table->unsignedInteger('evacuation_time_seconds')->default(0);
            $table->unsignedInteger('total_persons_evacuated')->default(0);
            $table->unsignedInteger('students_evacuated_count')->default(0);
            $table->unsignedInteger('staff_evacuated_count')->default(0);
            $table->string('assembly_zone_used');
            $table->string('civil_protection_partner')->nullable();
            $table->text('civil_protection_representatives')->nullable();
            $table->string('evaluation_rating', 30)->default('good')->index();
            $table->text('bottlenecks_and_remarks')->nullable();
            $table->text('corrective_measures')->nullable();
            $table->string('status', 30)->default('completed')->index();
            $table->timestamps();
        });

        // 6. School Hygiene & Safety Committee (CHS) Sessions
        Schema::create('safety_committee_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->index();
            $table->string('session_code', 30)->unique();
            $table->date('meeting_date');
            $table->string('session_type', 40)->default('ordinary_term_1')->index();
            $table->string('meeting_chair');
            $table->json('members_present')->nullable();
            $table->text('agenda_items');
            $table->text('deliberation_summary')->nullable();
            $table->text('decisions_and_recommendations')->nullable();
            $table->string('status', 30)->default('closed')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('safety_committee_meetings');
        Schema::dropIfExists('safety_evacuation_drills');
        Schema::dropIfExists('safety_fire_equipment');
        Schema::dropIfExists('school_safety_incidents');
        Schema::dropIfExists('safety_intervention_teams');
        Schema::dropIfExists('safety_emergency_plans');
    }
};
