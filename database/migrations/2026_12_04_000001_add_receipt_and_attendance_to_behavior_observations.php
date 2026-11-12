<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('behavior_observations', function (Blueprint $table): void {
            $table->boolean('is_guardian_receipt_returned')->default(false)->after('guardian_summons_notes');
            $table->dateTime('guardian_receipt_returned_at')->nullable()->after('is_guardian_receipt_returned');
            $table->string('guardian_attendance_status', 30)->default('pending')->after('guardian_receipt_returned_at');
            $table->text('guardian_attendance_notes')->nullable()->after('guardian_attendance_status');
        });
    }

    public function down(): void
    {
        Schema::table('behavior_observations', function (Blueprint $table): void {
            $table->dropColumn([
                'is_guardian_receipt_returned',
                'guardian_receipt_returned_at',
                'guardian_attendance_status',
                'guardian_attendance_notes',
            ]);
        });
    }
};
