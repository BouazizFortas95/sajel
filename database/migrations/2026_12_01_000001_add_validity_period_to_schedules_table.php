<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->date('valid_from')->nullable()->after('end_time');
            $table->date('valid_until')->nullable()->after('valid_from');

            $table->index(['valid_from', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex(['valid_from', 'valid_until']);
            $table->dropColumn(['valid_from', 'valid_until']);
        });
    }
};

