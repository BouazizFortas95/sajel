<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\SchoolSetting;
use Database\Seeders\FoundationalReferenceDataSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class DatabaseSwitchService
{
    public const MODE_SEEDED = 'seeded';

    public const MODE_CLEAN = 'clean';

    public function activeDbPath(): string
    {
        return database_path('database.sqlite');
    }

    public function cleanDbPath(): string
    {
        return database_path('database_clean.sqlite');
    }

    public function seededDbPath(): string
    {
        return database_path('database_seeded.sqlite');
    }

    public function stateMarkerPath(): string
    {
        return database_path('.active_db');
    }

    /**
     * Get the active database mode ('seeded' or 'clean').
     */
    public function getActiveMode(): string
    {
        if (File::exists($this->stateMarkerPath())) {
            $mode = trim(File::get($this->stateMarkerPath()));
            if (in_array($mode, [self::MODE_SEEDED, self::MODE_CLEAN], true)) {
                return $mode;
            }
        }

        // Auto-detect if marker file is missing
        try {
            $studentCount = Schema::hasTable('students') ? DB::table('students')->count() : 0;
            $classroomCount = Schema::hasTable('classrooms') ? DB::table('classrooms')->count() : 0;

            $detected = ($studentCount === 0 && $classroomCount === 0) ? self::MODE_CLEAN : self::MODE_SEEDED;
            File::put($this->stateMarkerPath(), $detected);

            return $detected;
        } catch (\Throwable) {
            return self::MODE_SEEDED;
        }
    }

    public function isClean(): bool
    {
        return $this->getActiveMode() === self::MODE_CLEAN;
    }

    public function isSeeded(): bool
    {
        return $this->getActiveMode() === self::MODE_SEEDED;
    }

    /**
     * Switch database between 'clean' and 'seeded'.
     *
     * @return array{success: bool, from: string, to: string, message: string}
     */
    public function switch(?string $targetMode = null): array
    {
        $currentMode = $this->getActiveMode();

        // Determine destination mode
        $newMode = match ($targetMode) {
            self::MODE_CLEAN => self::MODE_CLEAN,
            self::MODE_SEEDED => self::MODE_SEEDED,
            default => ($currentMode === self::MODE_SEEDED ? self::MODE_CLEAN : self::MODE_SEEDED),
        };

        // Ensure both source templates exist
        $this->ensureSourceDatabasesExist();

        // Persist current active changes back to its profile only when switching to a different mode
        if ($currentMode !== $newMode && File::exists($this->activeDbPath())) {
            if ($currentMode === self::MODE_SEEDED) {
                File::copy($this->activeDbPath(), $this->seededDbPath());
            } else {
                File::copy($this->activeDbPath(), $this->cleanDbPath());
            }
        }

        // Apply new database
        if ($newMode === self::MODE_CLEAN) {
            File::copy($this->cleanDbPath(), $this->activeDbPath());
            File::put($this->stateMarkerPath(), self::MODE_CLEAN);
            $message = __('تم التحويل إلى قاعدة البيانات النظيفة (فارغة — مطور وإعدادات المنصة فقط).');
        } else {
            File::copy($this->seededDbPath(), $this->activeDbPath());
            File::put($this->stateMarkerPath(), self::MODE_SEEDED);
            $message = __('تم التحويل إلى قاعدة البيانات التجريبية الشاملة (مملوءة بالبيانات الواقعية).');
        }

        // Reconnect and flush caches
        $this->flushCachesAndReconnect();

        return [
            'success' => true,
            'from' => $currentMode,
            'to' => $newMode,
            'message' => $message,
        ];
    }

    /**
     * Ensure both database_seeded.sqlite and database_clean.sqlite exist.
     */
    public function ensureSourceDatabasesExist(): void
    {
        // 1. If seeded does not exist, copy current active
        if (! File::exists($this->seededDbPath()) && File::exists($this->activeDbPath())) {
            File::copy($this->activeDbPath(), $this->seededDbPath());
        }

        // 2. If clean does not exist, initialize it
        if (! File::exists($this->cleanDbPath())) {
            $this->resetCleanDb();
        }
    }

    /**
     * Reset and recreate database_clean.sqlite with all migrations and only foundational reference data.
     */
    public function resetCleanDb(): void
    {
        // Create empty file
        File::put($this->cleanDbPath(), '');

        // Run migrations on sqlite_clean
        Artisan::call('migrate', [
            '--database' => 'sqlite_clean',
            '--force' => true,
        ]);

        // Seed foundational reference data (Developer, Platform settings, Subjects, Phases, Levels, Holidays, Strategies)
        (new FoundationalReferenceDataSeeder)->run('sqlite_clean');
    }

    /**
     * Flush caches and reconnect database.
     */
    public function flushCachesAndReconnect(): void
    {
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        SchoolSetting::flushCache();
        PlatformSetting::flushCache();

        try {
            Artisan::call('cache:clear');
        } catch (\Throwable) {
            // Safe fallback
        }
    }

    /**
     * Get database status summary.
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        $mode = $this->getActiveMode();

        $activeSize = File::exists($this->activeDbPath()) ? round(File::size($this->activeDbPath()) / (1024 * 1024), 2) : 0;
        $cleanSize = File::exists($this->cleanDbPath()) ? round(File::size($this->cleanDbPath()) / (1024 * 1024), 2) : 0;
        $seededSize = File::exists($this->seededDbPath()) ? round(File::size($this->seededDbPath()) / (1024 * 1024), 2) : 0;

        $userCount = 0;
        $studentCount = 0;
        $tableCount = 0;

        try {
            $userCount = DB::table('users')->count();
            $studentCount = Schema::hasTable('students') ? DB::table('students')->count() : 0;
            $tableCount = count(Schema::getTableListing(null));
        } catch (\Throwable) {
            // Ignore DB read failure during switch
        }

        return [
            'mode' => $mode,
            'mode_label' => $mode === self::MODE_CLEAN
                ? 'قاعدة البيانات النظيفة (فارغة — مطور وإعدادات أساسية فقط)'
                : 'قاعدة البيانات التجريبية الشاملة (مملوءة بالبيانات الواقعية)',
            'is_clean' => $mode === self::MODE_CLEAN,
            'is_seeded' => $mode === self::MODE_SEEDED,
            'users_count' => $userCount,
            'students_count' => $studentCount,
            'tables_count' => $tableCount,
            'files' => [
                'active' => ['path' => $this->activeDbPath(), 'size_mb' => $activeSize],
                'clean' => ['path' => $this->cleanDbPath(), 'size_mb' => $cleanSize],
                'seeded' => ['path' => $this->seededDbPath(), 'size_mb' => $seededSize],
            ],
        ];
    }
}
