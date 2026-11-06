<?php

namespace App\Enums;

enum ClubMemberRole: string
{
    case President = 'president';
    case VicePresident = 'vice_president';
    case Secretary = 'secretary';
    case ActiveMember = 'active_member';
    case HonoraryMember = 'honorary_member';

    public function label(): string
    {
        return match ($this) {
            self::President => __('رئيس النادي'),
            self::VicePresident => __('نائب الرئيس'),
            self::Secretary => __('أمين السر / المقرر'),
            self::ActiveMember => __('عضو منخرط'),
            self::HonoraryMember => __('عضو شرفي'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::President => 'danger',
            self::VicePresident => 'warning',
            self::Secretary => 'info',
            self::ActiveMember => 'success',
            self::HonoraryMember => 'purple',
        };
    }

    public function isBureau(): bool
    {
        return in_array($this, [self::President, self::VicePresident, self::Secretary], true);
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
