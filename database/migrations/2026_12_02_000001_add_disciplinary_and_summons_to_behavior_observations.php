<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('behavior_observations', function (Blueprint $table): void {
            $table->foreignId('disciplinary_record_id')
                ->nullable()
                ->after('schedule_id')
                ->constrained('disciplinary_records')
                ->nullOnDelete();

            $table->date('guardian_summons_sent_at')
                ->nullable()
                ->after('is_followed_up');

            $table->dateTime('guardian_summons_appointment_at')
                ->nullable()
                ->after('guardian_summons_sent_at');

            $table->text('guardian_summons_notes')
                ->nullable()
                ->after('guardian_summons_appointment_at');
        });
    }

    public function down(): void
    {
        Schema::table('behavior_observations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('disciplinary_record_id');
            $table->dropColumn([
                'guardian_summons_sent_at',
                'guardian_summons_appointment_at',
                'guardian_summons_notes',
            ]);
        });
    }
};
