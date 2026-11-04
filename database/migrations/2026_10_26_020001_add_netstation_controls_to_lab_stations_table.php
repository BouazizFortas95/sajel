<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_stations', function (Blueprint $table) {
            $table->string('ip_address')->nullable()->after('device_type');
            $table->boolean('is_locked')->default(false)->after('status');
            $table->string('lock_message')->nullable()->after('is_locked');
            $table->text('active_broadcast')->nullable()->after('lock_message');
            $table->boolean('internet_enabled')->default(true)->after('active_broadcast');
            $table->timestamp('last_ping_at')->nullable()->after('internet_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('lab_stations', function (Blueprint $table) {
            $table->dropColumn([
                'ip_address',
                'is_locked',
                'lock_message',
                'active_broadcast',
                'internet_enabled',
                'last_ping_at',
            ]);
        });
    }
};
