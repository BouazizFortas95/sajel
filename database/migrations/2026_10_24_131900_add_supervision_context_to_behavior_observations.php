<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('behavior_observations', function (Blueprint $table): void {
            $table->foreignId('schedule_id')
                ->nullable()
                ->after('student_id')
                ->constrained()
                ->nullOnDelete();
            $table->text('follow_up_notes')->nullable()->after('is_followed_up');
        });
    }

    public function down(): void
    {
        Schema::table('behavior_observations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('schedule_id');
            $table->dropColumn('follow_up_notes');
        });
    }
};
