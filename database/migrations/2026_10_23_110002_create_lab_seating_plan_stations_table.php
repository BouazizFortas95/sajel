<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_seating_plan_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seating_plan_id')->constrained('lab_seating_plans')->cascadeOnDelete();
            $table->foreignId('lab_station_id')->constrained('lab_stations')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->timestamps();

            $table->unique(['seating_plan_id', 'lab_station_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_seating_plan_stations');
    }
};
