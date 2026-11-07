<?php

namespace App\Enums;

enum SolidarityCampaignStatus: string
{
    case Planning = 'planning';
    case InReview = 'in_review';
    case Deliberated = 'deliberated';
    case SubmittedToDaira = 'submitted_to_daira';
    case Approved = 'approved';
    case Disbursed = 'disbursed';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Planning => __('قيد الإعداد وتجميع الملفات'),
            self::InReview => __('قيد التدقيق الإداري والاجتماعي'),
            self::Deliberated => __('تمت مداولة لجنة المؤسسة'),
            self::SubmittedToDaira => __('مرسل للجنة الدائرية والمديرية'),
            self::Approved => __('مؤشر ومصادق عليه رسميًا'),
            self::Disbursed => __('تم صرف المنحة عبر البريد'),
            self::Closed => __('مختتم ومؤرشف'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Planning => 'gray',
            self::InReview => 'warning',
            self::Deliberated => 'info',
            self::SubmittedToDaira => 'primary',
            self::Approved => 'success',
            self::Disbursed => 'success',
            self::Closed => 'gray',
        };
    }
}
