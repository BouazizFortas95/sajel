<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();

            // 1. Platform Identity & Branding
            $table->string('platform_name')->default('سَجِّل — Sajel');
            $table->string('platform_slogan')->nullable()->default('المنظومة الرقمية الشاملة لإدارة وتسيير المؤسسات التربوية');
            $table->text('meta_description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('dark_logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('primary_color')->default('#d97706');

            // 2. Developer & Technical Information
            $table->string('developer_name')->default('فريق تطوير منظومة سَجِّل');
            $table->string('developer_organization')->nullable()->default('سَجِّل للحلول البرمجية والتعليمية');
            $table->string('developer_website')->nullable()->default('https://sajel.dz');
            $table->string('developer_support_email')->nullable()->default('developer@sajel.dz');
            $table->string('developer_support_phone')->nullable()->default('+213 21 00 00 00');
            $table->string('version_tag')->default('v3.0.0-LTS');
            $table->string('footer_copyright_text')->nullable()->default('جميع الحقوق محفوظة © لمنظومة سَجِّل الرقمية — الجمهورية الجزائرية الديمقراطية الشعبية');

            // 3. Maintenance, Access & Announcements
            $table->boolean('is_maintenance_mode')->default(false);
            $table->text('maintenance_message')->nullable();
            $table->text('maintenance_allowed_ips')->nullable();
            $table->boolean('enable_public_registration')->default(false);
            $table->text('system_announcement')->nullable();
            $table->boolean('announcement_active')->default(false);
            $table->string('announcement_type')->default('info'); // info, warning, danger, success

            // 4. AI & System Engine Controls
            $table->boolean('enable_ai_assistant')->default(true);
            $table->string('ai_default_model')->default('gemini-2.0-flash');
            $table->unsignedInteger('max_file_upload_mb')->default(20);
            $table->unsignedInteger('session_lifetime_minutes')->default(120);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
