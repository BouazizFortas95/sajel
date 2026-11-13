<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->foreignId('pedagogical_memo_id')
                ->nullable()
                ->after('curriculum_resource_id')
                ->constrained('pedagogical_memos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pedagogical_memo_id');
        });
    }
};
