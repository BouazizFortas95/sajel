<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedagogical_remediations', function (Blueprint $table): void {
            $table->foreignId('schedule_id')
                ->nullable()
                ->after('student_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pedagogical_remediations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('schedule_id');
        });
    }
};
