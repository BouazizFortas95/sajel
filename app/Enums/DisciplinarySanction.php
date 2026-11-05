<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DisciplinarySanction: string implements HasColor, HasLabel
{
    case Warning = 'warning';
    case Reprimand = 'reprimand';
    case TemporaryExclusion = 'temporary_exclusion';
    case DefinitiveExclusion = 'definitive_exclusion';
    case CommunityService = 'community_service';
    case FinancialCompensation = 'financial_compensation';
    case Acquittal = 'acquittal';

    public function label(): string
    {
        return match ($this) {
            self::Warning => __('إنذار كتابي يُسجل في الملف'),
            self::Reprimand => __('توبيخ مع إشعار رسمي'),
            self::TemporaryExclusion => __('إقصاء مؤقت من المؤسسة'),
            self::DefinitiveExclusion => __('تحويل تأديبي إلى مؤسسة أخرى'),
            self::CommunityService => __('إجراء بديل: خدمة ذات نفع عام بالمؤسسة'),
            self::FinancialCompensation => __('إلزام الولي بالتعويض المالي عن الإتلاف'),
            self::Acquittal => __('حفظ الدعوى وتنبيه شفهي (براءة التلميذ)'),
        };
    }

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Warning => __('إنذار كتابي'),
            self::Reprimand => __('توبيخ'),
            self::TemporaryExclusion => __('إقصاء مؤقت'),
            self::DefinitiveExclusion => __('تحويل تأديبي'),
            self::CommunityService => __('خدمة نفع عام'),
            self::FinancialCompensation => __('تعويض مالي'),
            self::Acquittal => __('حفظ الدعوى'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Warning => 'amber',
            self::Reprimand => 'orange',
            self::TemporaryExclusion => 'rose',
            self::DefinitiveExclusion => 'red',
            self::CommunityService => 'blue',
            self::FinancialCompensation => 'purple',
            self::Acquittal => 'emerald',
        };
    }

    public function getColor(): string|array|null
    {
        return $this->color();
    }
}
