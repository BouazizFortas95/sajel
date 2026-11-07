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
        // Add custody nature to inventory allocations if not present
        if (Schema::hasTable('inventory_allocations') && ! Schema::hasColumn('inventory_allocations', 'custody_nature')) {
            Schema::table('inventory_allocations', function (Blueprint $table) {
                $table->string('custody_nature', 40)->default('general')->after('room_type')->index();
            });
        }

        // Inventory Recolements & Periodic Verification (بطاقات ومحاضر المطابقة السنوية والمعاينة - المنشور 143/97)
        Schema::create('inventory_recolements', function (Blueprint $table) {
            $table->id();
            $table->string('recolement_number', 50)->unique(); // e.g. REC/2026/001
            $table->foreignId('allocation_id')->nullable()->constrained('inventory_allocations')->nullOnDelete();
            $table->string('room_name');
            $table->string('room_type', 40)->default('classroom');
            $table->date('recolement_date')->index();
            $table->string('academic_year', 20)->default('2026/2027')->index();
            $table->foreignId('auditor_id')->nullable()->constrained('users')->nullOnDelete(); // رئيس لجنة المطابقة (المسير المالي)
            $table->foreignId('witness_id')->nullable()->constrained('users')->nullOnDelete(); // مسؤول العهدة / شاهد المطابقة
            $table->unsignedInteger('theoretical_count')->default(0); // عدد التجهيزات المقيدة نظرياً
            $table->unsignedInteger('verified_count')->default(0);    // عدد التجهيزات المعاينة فعلياً
            $table->unsignedInteger('conform_count')->default(0);     // التجهيزات المتطابقة والسليمة
            $table->unsignedInteger('missing_count')->default(0);     // التجهيزات المفقودة / الضائعة
            $table->unsignedInteger('surplus_count')->default(0);     // التجهيزات الزائدة غير المسجلة
            $table->unsignedInteger('damaged_count')->default(0);     // التجهيزات المتضررة / بحاجة لإصلاح أو إسقاط
            $table->string('status', 30)->default('conformed')->index(); // conformed, discrepancy, under_investigation
            $table->text('findings')->nullable(); // تقرير وملاحظات وتوصيات لجنة المعاينة والمطابقة
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_recolements');

        if (Schema::hasTable('inventory_allocations') && Schema::hasColumn('inventory_allocations', 'custody_nature')) {
            Schema::table('inventory_allocations', function (Blueprint $table) {
                $table->dropColumn('custody_nature');
            });
        }
    }
};
