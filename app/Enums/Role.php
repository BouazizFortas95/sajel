<?php

namespace App\Enums;

enum Role: string
{
    // 1. Organizational & Development
    case Developer = 'developer';

    // 2. School Leadership & Administration
    case Principal = 'principal';
    case Censor = 'censor';

    // 3. Education & Student Affairs Staff
    case GeneralSupervisor = 'general_supervisor';
    case EducationSupervisor = 'education_supervisor';
    case Counselor = 'counselor';
    case FinanceOfficer = 'finance_officer';

    // 4. Teaching Staff
    case HeadTeacher = 'head_teacher';
    case Teacher = 'teacher';

    // 5. Legacy / System Administrator alias
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Developer => __('مطور النظام'),
            self::Principal => __('مدير المؤسسة'),
            self::Censor => __('ناظر الدروس'),
            self::GeneralSupervisor => __('مستشار التربية'),
            self::EducationSupervisor => __('مشرف التربية'),
            self::Counselor => __('مستشار التوجيه والإرشاد'),
            self::FinanceOfficer => __('المقتصد / مسير مالي'),
            self::HeadTeacher => __('أستاذ رئيسي / منسق مادة'),
            self::Teacher => __('أستاذ'),
            self::Admin => __('المكلف بالرقمنة وأمانة النظام (Référent Numérique)'),
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::Developer => __('فريق التطوير والتقنية'),
            self::Principal, self::Censor, self::Admin => __('إدارة المؤسسة'),
            self::GeneralSupervisor, self::EducationSupervisor, self::Counselor, self::FinanceOfficer => __('موظفو التربية والتسيير'),
            self::HeadTeacher, self::Teacher => __('هيئة التدريس'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Developer => 'danger',
            self::Principal, self::Admin => 'primary',
            self::Censor => 'warning',
            self::GeneralSupervisor => 'indigo',
            self::EducationSupervisor => 'info',
            self::Counselor => 'purple',
            self::FinanceOfficer => 'success',
            self::HeadTeacher => 'teal',
            self::Teacher => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Developer => 'heroicon-o-code-bracket',
            self::Principal, self::Admin => 'heroicon-o-shield-check',
            self::Censor => 'heroicon-o-clipboard-document-check',
            self::GeneralSupervisor => 'heroicon-o-user-group',
            self::EducationSupervisor => 'heroicon-o-identification',
            self::Counselor => 'heroicon-o-heart',
            self::FinanceOfficer => 'heroicon-o-banknotes',
            self::HeadTeacher => 'heroicon-o-academic-cap',
            self::Teacher => 'heroicon-o-book-open',
        };
    }

    public function isDeveloper(): bool
    {
        return $this === self::Developer;
    }

    public function isPrincipal(): bool
    {
        return in_array($this, [self::Principal, self::Admin]);
    }

    public function isLeadership(): bool
    {
        return in_array($this, [self::Developer, self::Principal, self::Censor, self::Admin]);
    }

    public function isEducationStaff(): bool
    {
        return in_array($this, [
            self::GeneralSupervisor,
            self::EducationSupervisor,
            self::Counselor,
            self::FinanceOfficer,
        ]);
    }

    public function isTeachingStaff(): bool
    {
        return in_array($this, [self::HeadTeacher, self::Teacher]);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }

    public static function groupedOptions(): array
    {
        $grouped = [];
        foreach (self::cases() as $case) {
            $grouped[$case->category()][$case->value] = $case->label();
        }

        return $grouped;
    }
}
