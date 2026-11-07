<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlatformSettingsService
{
    /**
     * Get the active platform settings singleton model.
     */
    public function getSettings(): PlatformSetting
    {
        return PlatformSetting::query()->first() ?? PlatformSetting::create(PlatformSetting::defaults());
    }

    /**
     * Update the active platform settings.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(array $data): PlatformSetting
    {
        $setting = PlatformSetting::query()->first();

        if ($setting) {
            $setting->update($data);
        } else {
            $setting = PlatformSetting::create(array_merge(PlatformSetting::defaults(), $data));
        }

        PlatformSetting::purgeCache();

        return $setting;
    }

    /**
     * Toggle maintenance mode.
     */
    public function toggleMaintenance(bool $enabled, ?string $message = null): PlatformSetting
    {
        $data = ['is_maintenance_mode' => $enabled];
        if ($message !== null) {
            $data['maintenance_message'] = $message;
        }

        return $this->updateSettings($data);
    }

    /**
     * Gather detailed technical system telemetry for the developer hub.
     *
     * @return array<string, mixed>
     */
    public function getSystemTelemetry(): array
    {
        $tablesCount = 0;
        try {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tablesCount = count($tables);
            } else {
                $tablesCount = count(Schema::getTableListing());
            }
        } catch (\Throwable) {
            $driver = 'unknown';
        }

        $dbSizeMb = null;
        try {
            if ($driver === 'sqlite') {
                $dbPath = config('database.connections.sqlite.database');
                if (file_exists($dbPath)) {
                    $dbSizeMb = round(filesize($dbPath) / 1024 / 1024, 2);
                }
            }
        } catch (\Throwable) {
            $dbSizeMb = null;
        }

        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_os' => PHP_OS_FAMILY.' ('.php_uname('s').' '.php_uname('r').')',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI / Integrated',
            'db_driver' => $driver,
            'db_tables_count' => $tablesCount,
            'db_size_mb' => $dbSizeMb,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_limit' => ini_get('memory_limit') ?: 'N/A',
            'max_execution_time' => ini_get('max_execution_time') ?: 'N/A',
            'upload_max_filesize' => ini_get('upload_max_filesize') ?: 'N/A',
            'post_max_size' => ini_get('post_max_size') ?: 'N/A',
            'cache_driver' => config('cache.default', 'file'),
            'session_driver' => config('session.driver', 'file'),
            'queue_driver' => config('queue.default', 'sync'),
            'timezone' => config('app.timezone', 'Africa/Algiers'),
            'locale' => config('app.locale', 'ar'),
        ];
    }

    /**
     * Clear all application caches.
     *
     * @return array<string, string>
     */
    public function clearAllCaches(): array
    {
        $results = [];

        try {
            Artisan::call('cache:clear');
            $results['cache'] = 'تم تفريغ ذاكرة التخزين المؤقت للتطبيق بنجاح.';
        } catch (\Throwable $e) {
            $results['cache'] = 'فشل تفريغ الكاش: '.$e->getMessage();
        }

        try {
            Artisan::call('view:clear');
            $results['views'] = 'تم مسح قوالب العرض (Blade views) المترجمة بنجاح.';
        } catch (\Throwable $e) {
            $results['views'] = 'فشل مسح القوالب: '.$e->getMessage();
        }

        try {
            Artisan::call('route:clear');
            $results['routes'] = 'تم تنظيف ذاكرة المسارات (Routes) بنجاح.';
        } catch (\Throwable $e) {
            $results['routes'] = 'فشل تنظيف المسارات: '.$e->getMessage();
        }

        try {
            Artisan::call('config:clear');
            $results['config'] = 'تم تحديث ملفات الإعدادات والتهيئة بنجاح.';
        } catch (\Throwable $e) {
            $results['config'] = 'فشل تحديث الإعدادات: '.$e->getMessage();
        }

        PlatformSetting::purgeCache();

        return $results;
    }
}
