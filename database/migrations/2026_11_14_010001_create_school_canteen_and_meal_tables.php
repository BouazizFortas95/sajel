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
        Schema::create('canteen_food_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 40)->default('dry_goods')->index();
            $table->string('unit', 30)->default('كغ');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('safety_threshold', 10, 2)->default(10);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('canteen_daily_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->index();
            $table->date('sheet_date')->index();
            $table->string('reference_number', 100)->unique();
            $table->unsignedInteger('half_board_students_present')->default(0);
            $table->unsignedInteger('boarder_students_present')->default(0);
            $table->unsignedInteger('staff_beneficiaries_count')->default(0);
            $table->unsignedInteger('service_workers_count')->default(0);
            $table->unsignedInteger('total_beneficiaries_count')->default(0);
            $table->text('menu_description')->nullable();
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->decimal('cost_per_meal', 8, 2)->default(0);
            $table->string('storekeeper_name')->nullable();
            $table->string('kitchen_hygiene_status', 40)->default('good');
            $table->text('dietary_allergy_alerts')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('certified_at')->nullable();
            $table->foreignId('certified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['academic_year', 'sheet_date'], 'canteen_year_date_unique');
        });

        Schema::create('canteen_consumption_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canteen_daily_sheet_id')->constrained('canteen_daily_sheets')->cascadeOnDelete();
            $table->foreignId('canteen_food_item_id')->constrained('canteen_food_items')->cascadeOnDelete();
            $table->decimal('quantity_consumed', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_item_cost', 12, 2);
            $table->timestamps();
        });

        Schema::create('canteen_weekly_menus', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20)->index();
            $table->unsignedTinyInteger('week_number')->index();
            $table->date('week_start_date')->index();
            $table->text('sunday_menu')->nullable();
            $table->text('monday_menu')->nullable();
            $table->text('tuesday_menu')->nullable();
            $table->text('wednesday_menu')->nullable();
            $table->text('thursday_menu')->nullable();
            $table->unsignedInteger('nutrition_calories_approx')->default(850);
            $table->boolean('is_approved_by_doctor')->default(true);
            $table->boolean('approved_by_principal')->default(true);
            $table->timestamps();

            $table->unique(['academic_year', 'week_number'], 'canteen_year_week_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canteen_weekly_menus');
        Schema::dropIfExists('canteen_consumption_entries');
        Schema::dropIfExists('canteen_daily_sheets');
        Schema::dropIfExists('canteen_food_items');
    }
};

