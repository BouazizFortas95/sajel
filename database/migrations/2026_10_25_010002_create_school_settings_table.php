<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('school_name')->default('متوسطة الشهيد العربي بن مهيدي');
            $table->string('directorate_name')->default('مديرية التربية لولاية الوادي');
            $table->string('municipality_name')->nullable();
            $table->string('academic_year')->default('2026-2027');
            $table->string('principal_name')->nullable();
            $table->string('counselor_name')->nullable();
            $table->string('school_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
