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
        // 1. Inventory Items (سجل الجرد الدائم العام والخاص - المنشور 143/97)
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('inventory_number', 50)->unique(); // e.g. 02/0145/2026
            $table->string('category', 40)->default('furniture')->index(); // furniture, scientific_lab, audiovisual_it, sports, library_docs, kitchen_refectory
            $table->string('designation'); // اسم وتعيين العتاد
            $table->text('specifications')->nullable(); // المواصفات التقنية
            $table->date('acquisition_date')->index();
            $table->string('acquisition_source')->default('ميزانية المؤسسة');
            $table->decimal('acquisition_cost', 12, 2)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('location_type', 40)->default('classroom')->index(); // lab, classroom, office, library, canteen, store
            $table->string('location_name'); // اسم القاعة أو المخبر
            $table->foreignId('custodian_id')->nullable()->constrained('users')->nullOnDelete(); // المسئول عن العهدة
            $table->string('condition', 30)->default('good')->index(); // good, fair, damaged, reformed, lost
            $table->boolean('is_reformed')->default(false)->index();
            $table->date('reformed_at')->nullable();
            $table->text('reform_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Room Allocations (بطاقات التخصيص والمواقع)
        Schema::create('inventory_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('allocation_ref', 50)->unique();
            $table->string('room_name');
            $table->string('room_type', 40)->default('classroom');
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('academic_year', 20)->default('2026/2027')->index();
            $table->unsignedInteger('items_count')->default(0);
            $table->boolean('condition_verified')->default(true);
            $table->text('notes')->nullable();
            $table->date('signed_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Asset Reforms (محاضر الإسقاط والإتلاف ولجان المعاينة)
        Schema::create('inventory_reforms', function (Blueprint $table) {
            $table->id();
            $table->string('reform_number', 50)->unique(); // REF/2026/001
            $table->date('reform_date')->index();
            $table->string('academic_year', 20)->default('2026/2027')->index();
            $table->foreignId('committee_president_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_items_count')->default(0);
            $table->decimal('total_estimated_value', 12, 2)->default(0.00);
            $table->text('commission_observations')->nullable();
            $table->date('domains_notification_date')->nullable();
            $table->string('status', 30)->default('proposed')->index(); // proposed, approved_by_board, discharged
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reforms');
        Schema::dropIfExists('inventory_allocations');
        Schema::dropIfExists('inventory_items');
    }
};
