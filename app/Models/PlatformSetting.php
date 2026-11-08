<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Single-row technical platform and developer settings model.
 *
 * Distinct from institutional SchoolSetting: PlatformSetting manages the software
 * branding (logos, platform name, favicon), developer identity & technical credits,
 * maintenance mode, announcement banner, and technical platform controls.
 */
class PlatformSetting extends Model
{
    public const CACHE_KEY = 'sajel.platform_settings.current';

    protected $table = 'platform_settings';

    protected $fillable = [
        'platform_name',
        'platform_slogan',
        'meta_description',
        'logo_path',
        'dark_logo_path',
        'favicon_path',
        'primary_color',
        'developer_name',
        'developer_organization',
        'developer_website',
        'developer_support_email',
        'developer_support_phone',
        'version_tag',
        'footer_copyright_text',
        'is_maintenance_mode',
        'maintenance_message',
        'maintenance_allowed_ips',
        'enable_public_registration',
        'system_announcement',
        'announcement_active',
        'announcement_type',
        'enable_ai_assistant',
        'ai_default_model',
        'max_file_upload_mb',
        'session_lifetime_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_maintenance_mode' => 'boolean',
            'enable_public_registration' => 'boolean',
            'announcement_active' => 'boolean',
            'enable_ai_assistant' => 'boolean',
            'max_file_upload_mb' => 'integer',
            'session_lifetime_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function () {
            self::purgeCache();
        });

        static::deleted(function () {
            self::purgeCache();
        });
    }

    /**
     * Default platform settings.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'platform_name' => 'سَجِّل — Sajel',
            'platform_slogan' => 'المنظومة الرقمية الشاملة لإدارة وتسيير المؤسسات التربوية',
            'meta_description' => 'المنظومة الوطنية الموحدة لإدارة المؤسسات التعليمية الجزائرية والحياة المدرسية والرقمية.',
            'logo_path' => null,
            'dark_logo_path' => null,
            'favicon_path' => null,
            'primary_color' => '#d97706',
            'developer_name' => 'فريق تطوير منظومة سَجِّل',
            'developer_organization' => 'سَجِّل للحلول البرمجية والتعليمية',
            'developer_website' => 'https://sajel.dz',
            'developer_support_email' => 'developer@sajel.dz',
            'developer_support_phone' => '+213 21 00 00 00',
            'version_tag' => 'v3.0.0-LTS',
            'footer_copyright_text' => 'جميع الحقوق محفوظة © لمنظومة سَجِّل الرقمية — الجمهورية الجزائرية الديمقراطية الشعبية',
            'is_maintenance_mode' => false,
            'maintenance_message' => 'المنصة حالياً في فترة صيانة دورية وتحديثات أمنية مجدولة. نعتذر عن الإزعاج وسنعود للعمل قريباً.',
            'maintenance_allowed_ips' => null,
            'enable_public_registration' => false,
            'system_announcement' => null,
            'announcement_active' => false,
            'announcement_type' => 'info',
            'enable_ai_assistant' => true,
            'ai_default_model' => 'gemini-2.0-flash',
            'max_file_upload_mb' => 20,
            'session_lifetime_minutes' => 120,
        ];
    }

    /**
     * Fetch active platform settings with caching.
     */
    public static function current(): self
    {
        $setting = new self;
        $setting->forceFill(self::cachedAttributes());

        return $setting;
    }

    /**
     * Cached attributes array.
     *
     * @return array<string, mixed>
     */
    public static function cachedAttributes(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                $row = self::query()->first();
                if ($row) {
                    $attributes = $row->toArray();
                    $attributes['id'] = $row->id;

                    return $attributes;
                }
            } catch (Throwable) {
                // Table might not exist yet during migration
            }

            return self::defaults();
        });
    }

    /**
     * Clear the cached settings.
     */
    public static function purgeCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Alias for purgeCache for consistency across setting models.
     */
    public static function flushCache(): void
    {
        self::purgeCache();
    }

    /**
     * Public URL for the platform logo.
     */
    public function getLogoUrl(): ?string
    {
        if (blank($this->logo_path)) {
            return null;
        }

        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')) {
            return $this->logo_path;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Public URL for the dark mode platform logo.
     */
    public function getDarkLogoUrl(): ?string
    {
        if (blank($this->dark_logo_path)) {
            return $this->getLogoUrl();
        }

        if (str_starts_with($this->dark_logo_path, 'http://') || str_starts_with($this->dark_logo_path, 'https://')) {
            return $this->dark_logo_path;
        }

        return Storage::disk('public')->url($this->dark_logo_path);
    }

    /**
     * Public URL for the favicon.
     */
    public function getFaviconUrl(): ?string
    {
        if (blank($this->favicon_path)) {
            return null;
        }

        if (str_starts_with($this->favicon_path, 'http://') || str_starts_with($this->favicon_path, 'https://')) {
            return $this->favicon_path;
        }

        return Storage::disk('public')->url($this->favicon_path);
    }

    /**
     * Check if maintenance mode is enabled.
     */
    public function isMaintenanceActive(): bool
    {
        return (bool) $this->is_maintenance_mode;
    }

    /**
     * Check if announcement banner should be rendered.
     */
    public function isAnnouncementVisible(): bool
    {
        return (bool) $this->announcement_active && filled($this->system_announcement);
    }
}
