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
        // 1. School Cooperatives Identity & Executive Bureau
        Schema::create('school_cooperatives', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 9)->index();
            $table->string('cooperative_name')->default('التعاونية المدرسية');
            $table->string('approval_decree_number')->nullable();
            $table->date('foundation_date')->nullable();
            $table->string('motto_or_slogan')->nullable();
            $table->foreignId('coordinator_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('student_president_name')->nullable();
            $table->string('student_vice_president_name')->nullable();
            $table->string('student_secretary_name')->nullable();
            $table->string('student_treasurer_name')->nullable();
            $table->unsignedInteger('total_members_count')->default(0);
            $table->decimal('treasury_balance', 12, 2)->default(0.00);
            $table->text('annual_action_plan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['academic_year']);
        });

        // 2. Cooperative Projects & Solidarity Initiatives
        Schema::create('cooperative_projects_initiatives', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->unique();
            $table->string('academic_year', 9)->index();
            $table->foreignId('school_cooperative_id')->constrained('school_cooperatives')->cascadeOnDelete();
            $table->string('title');
            $table->string('project_type')->index();
            $table->string('target_audience')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('budget_allocated', 10, 2)->default(0.00);
            $table->decimal('budget_spent', 10, 2)->default(0.00);
            $table->string('status')->default('Planned')->index();
            $table->foreignId('coordinator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('student_leads_summary')->nullable();
            $table->text('achievements_notes')->nullable();
            $table->timestamps();
        });

        // 3. School Cooperative Financial & Solidarity Ledger
        Schema::create('cooperative_financial_ledger', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference')->unique();
            $table->string('academic_year', 9)->index();
            $table->foreignId('school_cooperative_id')->constrained('school_cooperatives')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('transaction_type')->index();
            $table->decimal('amount_dzd', 10, 2);
            $table->string('beneficiary_or_source');
            $table->string('payment_method')->default('Cash');
            $table->string('receipt_voucher_number')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 4. Pedagogical Garden Plots & Botanical Parcels
        Schema::create('pedagogical_garden_plots', function (Blueprint $table) {
            $table->id();
            $table->string('plot_code')->unique();
            $table->string('plot_name');
            $table->decimal('surface_area_sqm', 8, 2)->default(0.00);
            $table->string('soil_type')->nullable();
            $table->string('irrigation_type')->default('DripIrrigation');
            $table->string('location_in_school')->nullable();
            $table->foreignId('assigned_classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->foreignId('supervising_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('ActivePlanted')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Botanical Plants & Flora Catalog
        Schema::create('botanical_plants_inventory', function (Blueprint $table) {
            $table->id();
            $table->string('plant_code')->unique();
            $table->foreignId('pedagogical_garden_plot_id')->constrained('pedagogical_garden_plots')->cascadeOnDelete();
            $table->string('botanical_name')->nullable();
            $table->string('common_name_ar');
            $table->string('common_name_fr')->nullable();
            $table->string('plant_category')->index();
            $table->unsignedInteger('quantity_planted')->default(1);
            $table->date('planting_date')->nullable();
            $table->date('expected_harvest_date')->nullable();
            $table->string('health_status')->default('ThrivingGood')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Eco-School Green Club Actions & Afforestation
        Schema::create('eco_green_club_actions', function (Blueprint $table) {
            $table->id();
            $table->string('action_code')->unique();
            $table->string('academic_year', 9)->index();
            $table->string('action_title');
            $table->string('action_category')->index();
            $table->date('execution_date');
            $table->unsignedInteger('participants_count')->default(0);
            $table->unsignedInteger('trees_planted_count')->default(0);
            $table->decimal('waste_collected_kg', 8, 2)->default(0.00);
            $table->foreignId('supervising_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('location_details')->nullable();
            $table->string('partner_organization')->nullable();
            $table->text('impact_summary')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eco_green_club_actions');
        Schema::dropIfExists('botanical_plants_inventory');
        Schema::dropIfExists('pedagogical_garden_plots');
        Schema::dropIfExists('cooperative_financial_ledger');
        Schema::dropIfExists('cooperative_projects_initiatives');
        Schema::dropIfExists('school_cooperatives');
    }
};
