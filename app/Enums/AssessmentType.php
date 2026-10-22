<?php

namespace App\Enums;

enum AssessmentType: string
{
    case Quiz = 'quiz';
    case Midterm = 'midterm';
    case Exam = 'exam';
    case ContinuousAssessment = 'continuous_assessment';

    public function label(): string
    {
        return match ($this) {
            self::Quiz => __('اختبار قصير'),
            self::Midterm => __('فرض الفصل'),
            self::Exam => __('امتحان'),
            self::ContinuousAssessment => __('تقويم مستمر'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Quiz => 'info',
            self::Midterm => 'warning',
            self::Exam => 'danger',
            self::ContinuousAssessment => 'success',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
