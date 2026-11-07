<?php

namespace App\Enums;

enum SolidarityCommitteeRole: string
{
    case President = 'president';
    case FinancialReporter = 'financial_reporter';
    case CensorMember = 'censor_member';
    case GuidanceMember = 'guidance_member';
    case TeacherRepresentative = 'teacher_representative';
    case ParentAssociationRepresentative = 'parent_association_representative';
    case BoardMember = 'board_member';

    public function label(): string
    {
        return match ($this) {
            self::President => __('مدير المؤسسة (رئيس اللجنة)'),
            self::FinancialReporter => __('المقتصد / المسير المالي (المقرر)'),
            self::CensorMember => __('الناظر / مستشار التربية (عضو)'),
            self::GuidanceMember => __('مستشار التوجيه المدرسي (عضو)'),
            self::TeacherRepresentative => __('ممثل هيئة التدريس (عضو)'),
            self::ParentAssociationRepresentative => __('ممثل جمعية أولياء التلاميذ (عضو)'),
            self::BoardMember => __('ممثل مجلس الإدارة / التربية والتسيير'),
        };
    }

    public function defaultTitle(): string
    {
        return match ($this) {
            self::President => 'مدير المؤسسة ورئيس اللجنة',
            self::FinancialReporter => 'المسير المالي والمقتصد المقرر',
            self::CensorMember => 'ناظر المؤسسة / مستشار التربية',
            self::GuidanceMember => 'مستشار التوجيه والإرشاد المدرسي',
            self::TeacherRepresentative => 'أستاذ ممثل هيئة التدريس',
            self::ParentAssociationRepresentative => 'رئيس / ممثل جمعية أولياء التلاميذ',
            self::BoardMember => 'عضو مجلس الإدارة والتسيير',
        };
    }
}
