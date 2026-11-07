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
        // 1. School Board of Education & Management Sessions (CEG / COG)
        Schema::create('school_board_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->index();
            $table->string('session_code', 30)->unique();
            $table->string('board_type', 20)->default('ceg')->index(); // ceg | cog
            $table->string('session_term', 40)->default('term_1_opening')->index();
            $table->date('session_date');
            $table->string('session_time', 20)->default('14:30');
            $table->string('meeting_place')->default('قاعة الاجتماعات الرسمية');
            $table->foreignId('president_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('president_name');
            $table->foreignId('rapporteur_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rapporteur_name');
            $table->unsignedSmallInteger('quorum_required')->default(8);
            $table->unsignedSmallInteger('members_present_count')->default(0);
            $table->boolean('is_quorum_reached')->default(true);
            $table->text('agenda_items');
            $table->text('deliberation_details')->nullable();
            $table->text('decisions_summary')->nullable();
            $table->string('status', 30)->default('deliberated')->index();
            $table->date('approval_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. School Board Statutory and Elected Members
        Schema::create('school_board_members', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->index();
            $table->string('member_category', 40)->default('statutory')->index(); // statutory, elected_teacher, elected_staff, elected_parent
            $table->string('institution_role', 60);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('member_name');
            $table->date('mandate_start_date');
            $table->date('mandate_end_date');
            $table->boolean('is_active')->default(true)->index();
            $table->string('phone_number', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. School Strategic Multi-Year Project (Projet d'Établissement)
        Schema::create('school_projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code', 30)->unique();
            $table->string('title');
            $table->string('start_year', 20);
            $table->string('end_year', 20);
            $table->json('strategic_axes')->nullable();
            $table->json('quantitative_targets')->nullable();
            $table->text('action_plan')->nullable();
            $table->json('steering_committee_members')->nullable();
            $table->foreignId('board_session_id')->nullable()->constrained('school_board_sessions')->nullOnDelete();
            $table->string('status', 40)->default('in_progress')->index();
            $table->date('directorate_validation_date')->nullable();
            $table->decimal('completion_rate_percentage', 5, 2)->default(0.00);
            $table->text('evaluation_notes')->nullable();
            $table->timestamps();
        });

        // 4. School Internal Regulations (Règlement Intérieur Type)
        Schema::create('school_internal_regulations', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->index();
            $table->string('regulation_code', 30)->unique();
            $table->string('title');
            $table->string('version', 20)->default('1.0');
            $table->foreignId('adoption_board_session_id')->nullable()->constrained('school_board_sessions')->nullOnDelete();
            $table->date('adoption_date');
            $table->date('directorate_approval_date')->nullable();
            $table->text('key_rules_summary');
            $table->json('full_content_sections')->nullable();
            $table->boolean('is_current_active')->default(true)->index();
            $table->unsignedInteger('signed_undertakings_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Parents-Teachers Association (APE / جمعية أولياء التلاميذ)
        Schema::create('parents_associations', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->index();
            $table->string('association_name');
            $table->string('registration_number', 50)->nullable();
            $table->date('accreditation_date')->nullable();
            $table->date('general_assembly_date')->nullable();
            $table->string('president_name');
            $table->string('president_phone', 30)->nullable();
            $table->string('president_email', 100)->nullable();
            $table->string('vice_president_name')->nullable();
            $table->string('secretary_general_name')->nullable();
            $table->string('treasurer_name')->nullable();
            $table->unsignedInteger('registered_parents_count')->default(0);
            $table->unsignedInteger('active_membership_cards_count')->default(0);
            $table->decimal('annual_subscription_fee', 8, 2)->default(500.00);
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Parents Association General Assembly & Coordination Meetings
        Schema::create('parents_association_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parents_association_id')->constrained('parents_associations')->cascadeOnDelete();
            $table->string('academic_year', 20)->index();
            $table->string('meeting_code', 30)->unique();
            $table->string('meeting_type', 50)->default('ordinary_general_assembly')->index();
            $table->date('meeting_date');
            $table->unsignedInteger('attendees_count')->default(0);
            $table->text('agenda_topics');
            $table->text('deliberation_narrative')->nullable();
            $table->text('recommendations_and_actions')->nullable();
            $table->text('principal_visa_notes')->nullable();
            $table->string('status', 30)->default('approved')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parents_association_meetings');
        Schema::dropIfExists('parents_associations');
        Schema::dropIfExists('school_internal_regulations');
        Schema::dropIfExists('school_projects');
        Schema::dropIfExists('school_board_members');
        Schema::dropIfExists('school_board_sessions');
    }
};

