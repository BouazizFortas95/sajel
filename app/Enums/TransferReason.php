<?php

namespace App\Enums;

enum TransferReason: string
{
    case ChangeOfResidence = 'change_of_residence';
    case ParentEmployment = 'parent_employment';
    case Orientation = 'orientation';
    case Disciplinary = 'disciplinary';
    case Health = 'health';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ChangeOfResidence => __('تغيير مقر إقامة الولي'),
            self::ParentEmployment => __('التحاق بعمل الولي'),
            self::Orientation => __('توجيه / إعادة توجيه مدرسي'),
            self::Disciplinary => __('تحويل بقرار مجلس التأديب'),
            self::Health => __('أسباب صحية وملاءمة طبية'),
            self::Other => __('أسباب عائلية أو خاصة أخرى'),
        };
    }
}

