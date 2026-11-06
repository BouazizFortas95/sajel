<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('first_name_latin')->nullable()->after('first_name');
            $table->string('last_name_latin')->nullable()->after('last_name');
            $table->boolean('is_repeater')->default(false)->after('is_active');
            $table->string('address')->nullable()->after('guardian_relation');

            $table->index(['last_name_latin', 'first_name_latin']);
            $table->index('is_repeater');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['last_name_latin', 'first_name_latin']);
            $table->dropIndex(['is_repeater']);
            $table->dropColumn([
                'first_name_latin',
                'last_name_latin',
                'is_repeater',
                'address',
            ]);
        });
    }
};
