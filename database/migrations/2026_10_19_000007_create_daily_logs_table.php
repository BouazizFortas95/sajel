<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->date('date');
            $table->string('title');
            $table->json('content_data');
            $table->text('actual_content')->nullable();
            $table->text('homework')->nullable();
            $table->text('observations')->nullable();
            $table->string('status')->default('planned')->index();
            $table->timestamps();

            // A schedule can only have one daily log per day (one preparation per session).
            $table->unique(['schedule_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_logs');
    }
};
