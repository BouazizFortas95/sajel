<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('behavior_observations', function (Blueprint $table): void {
            $table->foreignId('recorded_by_id')
                ->nullable()
                ->after('student_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('pedagogical_remediations', function (Blueprint $table): void {
            $table->foreignId('recorded_by_id')
                ->nullable()
                ->after('student_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pedagogical_remediations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('recorded_by_id');
        });

        Schema::table('behavior_observations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('recorded_by_id');
        });
    }
};
