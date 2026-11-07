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
        // 1. School Sports & Cultural Association (الجمعية الثقافية والرياضية المدرسية ASCS)
        Schema::create('school_sports_associations', function (Blueprint $table) {
            $table->id();
            $table->string('association_name');
            $table->string('academic_year', 15)->index();
            $table->string('affiliation_number', 50)->nullable(); // Ligue de Wilaya du Sport Scolaire (LWSS)
            $table->string('approval_receipt_number', 60)->nullable(); // Récépissé d'agrément
            $table->date('approval_date')->nullable();
            $table->string('principal_president_name');
            $table->string('secretary_general_name'); // Professeur d'EPS
            $table->string('treasurer_name'); // Intendant / Économe
            $table->json('executive_bureau_members')->nullable();
            $table->decimal('annual_subscription_fee', 10, 2)->default(200.00); // Cotisation annuelle élève
            $table->unsignedInteger('active_members_count')->default(0);
            $table->string('status', 30)->default('Active'); // Active, Renewed, Suspended
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Sports & Cultural Disciplines (النشاطات والاختصاصات الرياضية والثقافية)
        Schema::create('sports_cultural_disciplines', function (Blueprint $table) {
            $table->id();
            $table->string('discipline_code', 30)->unique();
            $table->string('name_ar');
            $table->string('name_fr')->nullable();
            $table->string('category', 35); // TeamSport, IndividualSport, MindSport, ArtisticCultural
            $table->string('supervising_teacher_name');
            $table->foreignId('supervising_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('training_schedule')->nullable(); // e.g. الثلاثاء مساء / الجمعة صباحاً
            $table->string('target_genders', 25)->default('Mixed'); // Boys, Girls, Mixed
            $table->string('age_category', 30)->default('All'); // Benjamins, Minimes, Cadets, Juniors, All
            $table->unsignedSmallInteger('max_participants')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Student Sports & Cultural Licenses (إجازات وبطاقات التلاميذ الرياضية)
        Schema::create('student_sports_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_number', 40)->unique();
            $table->string('academic_year', 15)->index();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('discipline_id')->constrained('sports_cultural_disciplines')->cascadeOnDelete();
            $table->string('license_type', 35)->default('Competitive'); // Competitive, RecreationalSchoolPractice
            $table->boolean('medical_certificate_issued')->default(true);
            $table->date('medical_certificate_date')->nullable();
            $table->string('medical_examiner_name')->nullable(); // Médecin UDS / Praticien
            $table->string('medical_aptitude_verdict', 35)->default('FitApt'); // FitApt, UnfitTemporarily, UnfitPermanently
            $table->boolean('parental_consent_signed')->default(true);
            $table->boolean('insurance_covered')->default(true);
            $table->boolean('fee_paid')->default(true);
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('status', 30)->default('Active'); // Active, Suspended, Revoked, Expired
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Competitions, Tournaments & Events (البطولات والمنافسات الرياضية المدرسية)
        Schema::create('sports_competitions_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_code', 40)->unique();
            $table->string('academic_year', 15)->index();
            $table->foreignId('discipline_id')->nullable()->constrained('sports_cultural_disciplines')->nullOnDelete();
            $table->string('event_name');
            $table->string('competition_level', 35); // InternalInterClass, CommunalZone, DairaChampionship, WilayaFinals, NationalChampionship
            $table->date('event_date');
            $table->string('event_time', 20)->nullable();
            $table->string('location_venue'); // Salle Omnisports, Stade communal, Cour
            $table->string('host_institution')->nullable();
            $table->string('opponent_team_name')->nullable();
            $table->unsignedSmallInteger('our_score')->nullable();
            $table->unsignedSmallInteger('opponent_score')->nullable();
            $table->string('match_result', 30)->default('Upcoming'); // Upcoming, Win, Loss, Draw, Cancelled
            $table->string('ranking_achieved', 50)->nullable(); // e.g. المرتبة الأولى / الميدالية الذهبية
            $table->unsignedSmallInteger('fair_play_score')->default(10);
            $table->text('chaperones_list')->nullable();
            $table->boolean('transport_arranged')->default(false);
            $table->text('participation_notes')->nullable();
            $table->timestamps();
        });

        // 5. ASCS Financial Transactions Ledger (سجل مالية واشتراكات الجمعية الثقافية والرياضية)
        Schema::create('ascs_financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference', 40)->unique();
            $table->string('academic_year', 15)->index();
            $table->string('transaction_type', 35); // IncomeSubscription, IncomeSubsidy, IncomeDonation, ExpenseEquipment, ExpenseTransport, ExpenseCatering, ExpenseTrophiesAwards, ExpenseRegistrationFee
            $table->decimal('amount_dzd', 12, 2)->default(0);
            $table->date('transaction_date');
            $table->string('payment_method', 30)->default('Cash'); // Cash, PostalCheck, BankTransfer
            $table->string('invoice_or_receipt_ref', 60)->nullable();
            $table->string('description');
            $table->string('recorded_by_name');
            $table->boolean('treasurer_approved')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Sports Equipment Inventory (جرد عتاد ولوازم التربية البدنية والرياضية)
        Schema::create('sports_equipment_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_code', 30)->unique();
            $table->string('item_name');
            $table->foreignId('discipline_id')->nullable()->constrained('sports_cultural_disciplines')->nullOnDelete();
            $table->unsignedSmallInteger('quantity_total')->default(1);
            $table->unsignedSmallInteger('quantity_operational')->default(1);
            $table->unsignedSmallInteger('quantity_damaged')->default(0);
            $table->string('storage_location')->default('مخزن التربية البدنية والرياضية');
            $table->string('condition_rating', 30)->default('Good'); // New, Good, Acceptable, NeedsReplacement
            $table->date('last_inventory_date')->nullable();
            $table->string('purchased_academic_year', 15)->nullable();
            $table->string('acquisition_source', 35)->default('PurchasedASCS'); // PurchasedASCS, MinistryAllocation, Donation
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sports_equipment_inventories');
        Schema::dropIfExists('ascs_financial_transactions');
        Schema::dropIfExists('sports_competitions_events');
        Schema::dropIfExists('student_sports_licenses');
        Schema::dropIfExists('sports_cultural_disciplines');
        Schema::dropIfExists('school_sports_associations');
    }
};

