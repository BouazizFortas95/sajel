<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('parent_reception_day', 20)->nullable()->after('remember_token');
            $table->string('parent_reception_start_time', 10)->nullable()->after('parent_reception_day');
            $table->string('parent_reception_end_time', 10)->nullable()->after('parent_reception_start_time');
            $table->string('parent_reception_location')->nullable()->default('مكتب الاستشارة التربوية')->after('parent_reception_end_time');
            $table->text('parent_reception_notes')->nullable()->after('parent_reception_location');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'parent_reception_day',
                'parent_reception_start_time',
                'parent_reception_end_time',
                'parent_reception_location',
                'parent_reception_notes',
            ]);
        });
    }
};
