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
        Schema::table('teacher_absences', function (Blueprint $table) {
            // Work resumption protocol (محضر استئناف العمل)
            $table->dateTime('resumed_at')->nullable()->after('status');
            $table->string('resumption_pv_number')->nullable()->after('resumed_at');
            $table->text('resumption_notes')->nullable()->after('resumption_pv_number');

            // Salary deduction notifications (إشعارات الخصم من الراتب للغيابات غير المبررة)
            $table->boolean('is_deduction_notified')->default(false)->after('resumption_notes');
            $table->unsignedInteger('deduction_days')->nullable()->after('is_deduction_notified');
            $table->string('deduction_ref_number')->nullable()->after('deduction_days');
            $table->date('deduction_notified_at')->nullable()->after('deduction_ref_number');

            // Administrative requisition mandates (التسخيرات الإدارية لتغطية الحصص)
            $table->boolean('is_requisition')->default(false)->after('deduction_notified_at');
            $table->string('requisition_ref_number')->nullable()->after('is_requisition');
            $table->text('requisition_justification')->nullable()->after('requisition_ref_number');

            $table->index('resumed_at');
            $table->index('is_deduction_notified');
            $table->index('is_requisition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_absences', function (Blueprint $table) {
            $table->dropIndex(['resumed_at']);
            $table->dropIndex(['is_deduction_notified']);
            $table->dropIndex(['is_requisition']);

            $table->dropColumn([
                'resumed_at',
                'resumption_pv_number',
                'resumption_notes',
                'is_deduction_notified',
                'deduction_days',
                'deduction_ref_number',
                'deduction_notified_at',
                'is_requisition',
                'requisition_ref_number',
                'requisition_justification',
            ]);
        });
    }
};
