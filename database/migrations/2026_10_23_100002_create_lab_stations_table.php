<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained('labs')->cascadeOnDelete();
            $table->unsignedInteger('station_number');
            $table->string('device_type');
            $table->string('status')->default('working');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['lab_id', 'station_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_stations');
    }
};
