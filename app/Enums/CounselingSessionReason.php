<?php

namespace App\Enums;

enum CounselingSessionReason: string
{
    case AcademicDifficulty = 'academic_difficulty';
    case BehavioralIssue = 'behavioral_issue';
    case FamilyCrisis = 'family_crisis';
    case CareerGuidance = 'career_guidance';
    case Absenteeism = 'absenteeism';
    case PsychologicalSupport = 'psychological_support';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::AcademicDifficulty => __('صعوبات تعلم وتحصيل دراسي'),
            self::BehavioralIssue => __('اضطرابات سلوكية ومواظبة'),
            self::FamilyCrisis => __('أزمات وظروف أسرية خاصة'),
            self::CareerGuidance => __('إرشاد وتوجيه مدرسي ومهني'),
            self::Absenteeism => __('غيابات متكررة وخطر التسرب'),
            self::PsychologicalSupport => __('دعم نفسي وانفعالي'),
            self::Other => __('أسباب ومتابعات أخرى'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AcademicDifficulty => 'amber',
            self::BehavioralIssue => 'rose',
            self::FamilyCrisis => 'purple',
            self::CareerGuidance => 'primary',
            self::Absenteeism => 'danger',
            self::PsychologicalSupport => 'teal',
            self::Other => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::AcademicDifficulty => 'heroicon-o-academic-cap',
            self::BehavioralIssue => 'heroicon-o-exclamation-triangle',
            self::FamilyCrisis => 'heroicon-o-home-modern',
            self::CareerGuidance => 'heroicon-o-arrow-trending-up',
            self::Absenteeism => 'heroicon-o-user-minus',
            self::PsychologicalSupport => 'heroicon-o-heart',
            self::Other => 'heroicon-o-document-text',
        };
    }
}
