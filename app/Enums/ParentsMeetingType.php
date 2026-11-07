<?php

namespace App\Enums;

enum ParentsMeetingType: string
{
    case OrdinaryGeneralAssembly = 'ordinary_general_assembly';
    case ExtraordinaryGeneralAssembly = 'extraordinary_general_assembly';
    case JointSchoolMeeting = 'joint_school_meeting';

    public function label(): string
    {
        return match ($this) {
            self::OrdinaryGeneralAssembly => __('جمعية عامة عادية وانتخاب المكتب (Assemblée générale ordinaire)'),
            self::ExtraordinaryGeneralAssembly => __('جمعية عامة استثنائية (Assemblée générale extraordinaire)'),
            self::JointSchoolMeeting => __('جلسة تنسيقية مشتركة مع إدارة المؤسسة (Réunion de concertation)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::OrdinaryGeneralAssembly => 'success',
            self::ExtraordinaryGeneralAssembly => 'warning',
            self::JointSchoolMeeting => 'info',
        };
    }
}

