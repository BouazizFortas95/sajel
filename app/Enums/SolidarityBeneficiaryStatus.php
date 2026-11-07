<?php

namespace App\Enums;

enum SolidarityBeneficiaryStatus: string
{
    case Submitted = 'submitted';
    case VerifiedBySchool = 'verified_by_school';
    case DeliberatedApproved = 'deliberated_approved';
    case ApprovedByDaira = 'approved_by_daira';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => __('ملف مودع'),
            self::VerifiedBySchool => __('مدقق ومستوفٍ بالمؤسسة'),
            self::DeliberatedApproved => __('مقبول بمداولة اللجنة'),
            self::ApprovedByDaira => __('مصادق عليه من الدائرة'),
            self::Paid => __('تم الصرف والتحويل'),
            self::Rejected => __('ملف مرفوض'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Submitted => 'gray',
            self::VerifiedBySchool => 'info',
            self::DeliberatedApproved => 'warning',
            self::ApprovedByDaira => 'primary',
            self::Paid => 'success',
            self::Rejected => 'danger',
        };
    }
}
