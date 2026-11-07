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
        // 1. School Building Facilities (الهياكل والمنشآت المدرسية)
        Schema::create('school_building_facilities', function (Blueprint $table) {
            $table->id();
            $table->string('facility_code', 30)->unique();
            $table->string('facility_name');
            $table->string('facility_type', 40); // PedagogicalWing, AdministrativeWing, BoardingDormitory, RefectoryKitchen, SportsHall, WorkshopLab, StaffHousing, ExteriorYard
            $table->string('building_block', 50)->nullable();
            $table->unsignedSmallInteger('total_rooms_count')->default(1);
            $table->string('condition_rating', 30)->default('Good'); // Good, Acceptable, NeedsRestoration, Dilapidated
            $table->boolean('has_heating')->default(true);
            $table->boolean('has_water_connection')->default(true);
            $table->boolean('has_gas_connection')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Maintenance Work Orders (أوامر الأشغال والتدخلات التقنية)
        Schema::create('maintenance_work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('work_order_number', 40)->unique();
            $table->string('academic_year', 15)->index();
            $table->foreignId('facility_id')->nullable()->constrained('school_building_facilities')->nullOnDelete();
            $table->string('exact_location');
            $table->string('category', 35); // Electricity, PlumbingSanitary, CentralHeating, CarpentryLocksmith, MasonryPainting, GlazingRoofing
            $table->string('priority_level', 30)->default('Normal'); // Low, Normal, Urgent, EmergencySafety
            $table->text('issue_description');
            $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reported_by_name');
            $table->date('report_date');
            $table->date('scheduled_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->string('intervention_type', 30)->default('InternalStaff'); // InternalStaff, ExternalContractor
            $table->string('contractor_name')->nullable();
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->decimal('actual_cost', 12, 2)->default(0);
            $table->string('status', 30)->default('Reported'); // Reported, Scheduled, InProgress, Completed, Cancelled
            $table->text('technician_notes')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Central Heating & Boilers Safety Logs (سجل التدفئة المركزية وسلامة الغلايات)
        Schema::create('central_heating_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_reference', 40)->unique();
            $table->string('academic_year', 15)->index();
            $table->string('boiler_code', 30);
            $table->string('boiler_brand_model')->nullable();
            $table->unsignedSmallInteger('installation_year')->nullable();
            $table->string('fuel_type', 30)->default('NaturalGas'); // NaturalGas, PropaneLpg, FuelOil
            $table->date('inspection_date');
            $table->string('inspector_entity'); // InternalTechnician, CivilProtection, ApprovedControlOffice, Sonelgaz
            $table->string('burner_operational_state', 35)->default('Optimal'); // Optimal, FunctionalWithAnomalies, DefectiveDanger
            $table->boolean('safety_valve_tested')->default(true);
            $table->decimal('pressure_bar', 4, 2)->default(1.50);
            $table->unsignedSmallInteger('water_temp_celsius')->default(65);
            $table->boolean('co_detector_tested')->default(true);
            $table->boolean('room_ventilation_ok')->default(true);
            $table->boolean('chimneys_cleaned')->default(true);
            $table->string('status', 30)->default('CompliantApproved'); // CompliantApproved, ConditionalUse, ShutDownEmergency
            $table->text('observations_and_repairs')->nullable();
            $table->string('certified_by_name');
            $table->timestamps();
        });

        // 4. Water Tank Hygiene & Disinfection Inspections (سجل نظافة وتطهير باشات وخزانات الماء الشروب)
        Schema::create('water_tank_hygiene_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_reference', 40)->unique();
            $table->string('academic_year', 15)->index();
            $table->string('tank_name_code', 40);
            $table->string('tank_type', 35)->default('UndergroundBache'); // UndergroundBache, ElevatedRoofTank, GroundCistern
            $table->decimal('capacity_cubic_meters', 8, 2)->default(10.00);
            $table->date('inspection_date');
            $table->string('operation_type', 40)->default('ChlorinationDisinfection'); // VisualInspection, WashingEmptying, ChlorinationDisinfection, BacteriologicalLabAnalysis
            $table->string('chemical_agent_used')->nullable(); // Eau de Javel, Chlore, Hypochlorite
            $table->string('executed_by'); // SchoolHygieneStaff, MunicipalHygieneOffice_BHM, ApprovedEnterprise
            $table->decimal('residual_chlorine_ppm', 4, 2)->default(0.50);
            $table->string('water_potable_clearance', 35)->default('CompliantPotable'); // CompliantPotable, ContaminatedNonPotable, PendingAnalysis
            $table->date('next_disinfection_due_date')->nullable();
            $table->string('certification_ref')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Energy & Utility Meters Registry (سجل عدادات الكهرباء والغاز والماء)
        Schema::create('energy_utility_meters', function (Blueprint $table) {
            $table->id();
            $table->string('meter_code', 30)->unique();
            $table->string('meter_type', 30); // Electricity, NaturalGas, PotableWater
            $table->string('utility_provider', 40)->default('Sonelgaz'); // Sonelgaz, Ade, NaftalPropane
            $table->string('meter_serial_number', 50);
            $table->string('meter_location');
            $table->string('subscribed_power_or_flow', 50)->nullable(); // e.g. 380V / 30A, Calibre G16, Diamètre 40mm
            $table->string('tariff_category', 50)->nullable(); // e.g. Tarif Moyenne Tension, Tarif 54 Gaz, Tarif Administrations
            $table->string('unit_of_measure', 20)->default('KWh'); // KWh, M3
            $table->boolean('is_active')->default(true);
            $table->date('installation_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Utility Consumption & Invoice Audits (سجل استهلاك الطاقة والمرافق ومطابقة الفواتير)
        Schema::create('utility_consumption_records', function (Blueprint $table) {
            $table->id();
            $table->string('bill_reference', 40)->unique();
            $table->string('academic_year', 15)->index();
            $table->foreignId('energy_utility_meter_id')->constrained('energy_utility_meters')->cascadeOnDelete();
            $table->string('billing_period_quarter', 20)->default('Q1'); // Q1, Q2, Q3, Q4, Monthly
            $table->date('reading_date_start');
            $table->date('reading_date_end');
            $table->unsignedBigInteger('previous_index');
            $table->unsignedBigInteger('current_index');
            $table->unsignedBigInteger('consumption_units');
            $table->decimal('invoiced_amount_dzd', 12, 2)->default(0);
            $table->string('invoice_number', 50)->nullable();
            $table->date('invoice_reception_date')->nullable();
            $table->string('payment_status', 35)->default('ReceivedPendingAudit'); // ReceivedPendingAudit, AuditedApproved, MandatedPaid, ContestedOverbilled
            $table->string('meter_auditor_name');
            $table->boolean('anomaly_detected')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utility_consumption_records');
        Schema::dropIfExists('energy_utility_meters');
        Schema::dropIfExists('water_tank_hygiene_inspections');
        Schema::dropIfExists('central_heating_logs');
        Schema::dropIfExists('maintenance_work_orders');
        Schema::dropIfExists('school_building_facilities');
    }
};

