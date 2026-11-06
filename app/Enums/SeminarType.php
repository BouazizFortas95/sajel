<?php

namespace App\Enums;

enum SeminarType: string
{
    case InspectoralSeminar = 'inspectoral_seminar';
    case ModelLesson = 'model_lesson';
    case StudyDay = 'study_day';
    case ExchangeVisit = 'exchange_visit';

    public function label(): string
    {
        return match ($this) {
            self::InspectoralSeminar => __('ندوة تربوية مقاطعية'),
            self::ModelLesson => __('درس نموذجي توضيحي'),
            self::StudyDay => __('يوم دراسي تكويني'),
            self::ExchangeVisit => __('زيارة بيداغوجية متبادلة'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InspectoralSeminar => 'info',
            self::ModelLesson => 'success',
            self::StudyDay => 'warning',
            self::ExchangeVisit => 'primary',
        };
    }
}

