<?php

namespace App\Enums;

enum SchoolProjectStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case ValidatedByDirectorate = 'validated_by_directorate';
    case InProgress = 'in_progress';
    case Evaluated = 'evaluated';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('قيد الصياغة والإعداد (Élaboration)'),
            self::Submitted => __('معروض على مجلس التربية (Soumis au Conseil)'),
            self::ValidatedByDirectorate => __('معتمد ومؤشر من المديرية (Validé par la Direction)'),
            self::InProgress => __('قيد التنفيذ والمتابعة (En exécution)'),
            self::Evaluated => __('تم تقييم الحصيلة السنوية (Évalué)'),
            self::Archived => __('مؤرشف (Archivé)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'info',
            self::ValidatedByDirectorate => 'success',
            self::InProgress => 'primary',
            self::Evaluated => 'warning',
            self::Archived => 'gray',
        };
    }
}

