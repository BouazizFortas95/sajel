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
        Schema::create('school_census_records', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year')->default('2026/2027');
            $table->date('census_date')->default('2026-10-15');
            $table->string('reference_number')->nullable();
            $table->string('status')->default('draft'); // draft, certified
            $table->dateTime('certified_at')->nullable();
            $table->foreignId('certified_by_id')->nullable()->constrained('users')->nullOnDelete();

            // Infrastructure & Physical facilities
            $table->integer('ordinary_classrooms_count')->default(12);
            $table->integer('specialized_rooms_count')->default(2);
            $table->integer('science_labs_count')->default(2);
            $table->integer('computer_labs_count')->default(1);
            $table->integer('library_capacity')->default(40);
            $table->integer('canteen_capacity')->default(200);

            // Staffing numbers
            $table->integer('administrative_staff_count')->default(4);
            $table->integer('education_supervisors_count')->default(6);
            $table->integer('service_workers_count')->default(8);

            // Frozen historical snapshot of tables at certification time
            $table->json('snapshot_data')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index('academic_year');
            $table->index('census_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_census_records');
    }
};
