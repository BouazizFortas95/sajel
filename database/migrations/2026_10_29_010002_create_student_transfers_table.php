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
        Schema::create('student_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('transfer_type'); // 'inward', 'outward'
            $table->string('academic_year')->default('2026/2027');
            $table->date('transfer_date');
            $table->string('reference_number')->nullable();
            $table->date('reference_date')->nullable();
            $table->string('other_school_name');
            $table->string('other_directorate_name')->nullable();
            $table->string('reason')->default('change_of_residence');
            $table->string('status')->default('approved');
            $table->boolean('school_file_forwarded')->default(false);
            $table->date('school_file_forwarded_at')->nullable();
            $table->boolean('clearance_attached')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'transfer_type']);
            $table->index(['academic_year', 'transfer_type']);
            $table->index('transfer_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_transfers');
    }
};
