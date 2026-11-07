<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Conforms to Algerian Executive Decree 21-61 on the special 5,000 DZD school allowance
     * and Ministry of National Education circulars on school solidarity and textbook/school supply grants.
     */
    public function up(): void
    {
        // 1. School Solidarity Campaigns (الحملات السنوية لمنحة التمدرس والتضامن المدرسي)
        Schema::create('school_solidarity_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('campaign_year')->index();
            $table->string('academic_year', 20)->index();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('grant_amount', 10, 2)->default(5000.00);
            $table->string('status', 30)->default('planning');
            $table->decimal('allocated_budget', 12, 2)->nullable();
            $table->unsignedInteger('approved_beneficiaries_count')->default(0);
            $table->decimal('total_disbursed_amount', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // 2. Student Solidarity Beneficiaries (سجل ملفات التلاميذ المستفيدين من المنحة الخاصة 5000 دج)
        Schema::create('student_solidarity_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('school_solidarity_campaigns')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('file_number', 50)->unique();
            $table->string('eligibility_category', 40); // orphan, needy_no_income, low_income, special_needs, national_tragedy
            $table->string('guardian_type', 30)->default('father'); // father, mother, legal_tutor
            $table->string('guardian_national_id', 50);
            $table->string('guardian_full_name');
            $table->string('guardian_profession')->nullable();
            $table->decimal('monthly_income', 10, 2)->default(0.00);
            $table->string('ccp_account_number', 30)->nullable();
            $table->string('ccp_key', 5)->nullable();
            $table->string('rip_number', 40)->nullable();
            $table->string('status', 30)->default('submitted'); // submitted, verified_by_school, deliberated_approved, approved_by_daira, rejected, paid
            $table->string('rejection_reason')->nullable();
            $table->string('payment_reference')->nullable();
            $table->date('payment_date')->nullable();
            $table->json('submitted_documents')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['campaign_id', 'student_id']);
        });

        // 3. School Solidarity Committee Members (أعضاء لجنة التضامن المدرسي بالمؤسسة)
        Schema::create('school_solidarity_committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('school_solidarity_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('member_name');
            $table->string('committee_role', 40); // president, financial_reporter, censor_member, guidance_member, teacher_representative, parent_association_representative
            $table->string('institution_role_label');
            $table->boolean('attended_deliberation')->default(true);
            $table->string('signature_notes')->nullable();
            $table->timestamps();
        });

        // 4. School Solidarity Aid Distributions (سجل توزيع المساعدات العينية ومجانية الكتاب المدرسي والحقيبة)
        Schema::create('school_solidarity_aid_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('school_solidarity_campaigns')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('aid_type', 40); // free_textbooks, school_bag, stationery_supplies, clothing_aid, other
            $table->string('voucher_number', 50)->unique();
            $table->date('distribution_date');
            $table->text('items_description');
            $table->decimal('estimated_value', 10, 2)->default(0.00);
            $table->string('donor_entity')->default('وزارة التربية الوطنية');
            $table->boolean('received_by_guardian')->default(true);
            $table->boolean('guardian_signature_confirmed')->default(true);
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_solidarity_aid_distributions');
        Schema::dropIfExists('school_solidarity_committee_members');
        Schema::dropIfExists('student_solidarity_beneficiaries');
        Schema::dropIfExists('school_solidarity_campaigns');
    }
};
