<?php

namespace App\Enums;

enum PedagogicalCouncilType: string
{
    case SubjectCouncil = 'subject_council';
    case TermCoordination = 'term_coordination';
    case AdministrativeCoordination = 'administrative_coordination';
    case InternalSeminar = 'internal_seminar';

    public function label(): string
    {
        return match ($this) {
            self::SubjectCouncil => __('مجلس التعليم للمادة'),
            self::TermCoordination => __('جلسة التنسيق البيداغوجي الفصلي'),
            self::AdministrativeCoordination => __('مجلس التنسيق الإداري والتربوي'),
            self::InternalSeminar => __('ندوة تربوية داخلية بالمؤسسة'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SubjectCouncil => 'info',
            self::TermCoordination => 'warning',
            self::AdministrativeCoordination => 'success',
            self::InternalSeminar => 'primary',
        };
    }
}

