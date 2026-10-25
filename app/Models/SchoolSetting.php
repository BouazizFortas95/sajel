<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Single-row school identity used by every official print/PDF document.
 *
 * The row is optional: when the settings table is empty (or not migrated yet)
 * `current()` returns an unsaved model carrying the official defaults, so
 * printed documents always render without throwing.
 */
class SchoolSetting extends Model
{
    public const CACHE_KEY = 'sajel.school_settings.current';

    protected $fillable = [
        'school_name',
        'directorate_name',
        'municipality_name',
        'academic_year',
        'principal_name',
        'counselor_name',
        'school_code',
    ];

    /**
     * Official defaults for an Algerian middle school.
     *
     * @return array<string, string|null>
     */
    public static function defaults(): array
    {
        return [
            'school_name' => 'متوسطة الشهيد العربي بن مهيدي',
            'directorate_name' => 'مديرية التربية لولاية الوادي',
            'municipality_name' => null,
            'academic_year' => '2026-2027',
            'principal_name' => null,
            'counselor_name' => null,
            'school_code' => null,
        ];
    }

    /**
     * The active school identity, cached until the row is saved or deleted.
     */
    public static function current(): self
    {
        $setting = new self;
        $setting->forceFill(self::cachedAttributes());

        return $setting;
    }

    public static function flushCache(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            // A missing cache table must never break printing.
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function cachedAttributes(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function (): array {
                $record = self::query()->orderBy('id')->first();

                return $record?->attributesToArray() ?? self::defaults();
            });
        } catch (Throwable) {
            return self::defaults();
        }
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            self::flushCache();
        });

        static::deleted(function (): void {
            self::flushCache();
        });
    }
}
