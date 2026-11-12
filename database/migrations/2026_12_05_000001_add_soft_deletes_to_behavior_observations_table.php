<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('behavior_observations', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('deletion_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('behavior_observations', function (Blueprint $table) {
            $table->dropForeign(['deleted_by_id']);
            $table->dropColumn(['deleted_by_id', 'deletion_reason']);
            $table->dropSoftDeletes();
        });
    }
};
