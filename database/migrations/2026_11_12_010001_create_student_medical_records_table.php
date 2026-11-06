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
        Schema::create('student_medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 20)->index();
            $table->date('screening_date')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('uds_name')->nullable();

            // Biometrics
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->string('blood_type', 10)->nullable()->default('unknown');

            // Vision & Hearing & Dental
            $table->string('visual_acuity_right', 10)->nullable();
            $table->string('visual_acuity_left', 10)->nullable();
            $table->boolean('wears_glasses')->default(false);
            $table->string('hearing_condition', 30)->nullable()->default('normal');
            $table->unsignedTinyInteger('dental_caries_count')->default(0);
            $table->string('dental_hygiene', 30)->nullable()->default('good');

            // Chronic illnesses & PAI
            $table->boolean('has_chronic_illness')->default(false)->index();
            $table->string('chronic_condition_type', 30)->nullable()->default('none')->index();
            $table->text('chronic_illness_details')->nullable();
            $table->text('emergency_protocol_notes')->nullable();
            $table->boolean('emergency_medication_at_school')->default(false);

            // Vaccinations
            $table->boolean('vaccinations_up_to_date')->default(true);
            $table->text('vaccination_notes')->nullable();

            // Physical Education & Sports Exemption (EPS)
            $table->string('eps_dispensation_status', 30)->default('fit')->index();
            $table->date('eps_dispensation_start_date')->nullable();
            $table->date('eps_dispensation_end_date')->nullable();
            $table->string('eps_dispensation_reason')->nullable();
            $table->string('eps_medical_certificate_ref')->nullable();

            // Clinical Notes, Follow-up & Specialty Referrals
            $table->text('general_clinical_notes')->nullable();
            $table->boolean('follow_up_required')->default(false)->index();
            $table->string('specialist_referral')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'academic_year'], 'student_medical_academic_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_medical_records');
    }
};

