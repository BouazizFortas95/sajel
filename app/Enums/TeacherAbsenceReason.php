<?php

namespace App\Enums;

enum TeacherAbsenceReason: string
{
    case SickLeave = 'sick_leave';
    case OfficialMission = 'official_mission';
    case AuthorizedLeave = 'authorized_leave';
    case MaternityLeave = 'maternity_leave';
    case Emergency = 'emergency';
    case Unjustified = 'unjustified';

    public function label(): string
    {
        return match ($this) {
            self::SickLeave => __('عطلة مرضية'),
            self::OfficialMission => __('مهمة رسمية أو تكوين'),
            self::AuthorizedLeave => __('رخصة غياب / إذن بالغياب'),
            self::MaternityLeave => __('عطلة أمومة'),
            self::Emergency => __('طارئ عائلي / قاهر'),
            self::Unjustified => __('غياب غير مبرر'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SickLeave => 'warning',
            self::OfficialMission => 'info',
            self::AuthorizedLeave => 'primary',
            self::MaternityLeave => 'purple',
            self::Emergency => 'amber',
            self::Unjustified => 'danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}

