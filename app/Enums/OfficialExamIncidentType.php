<?php

namespace App\Enums;

enum OfficialExamIncidentType: string
{
    case EnvelopeOpening = 'envelope_opening';
    case CheatingAttempt = 'cheating_attempt';
    case CandidateAbsence = 'candidate_absence';
    case MedicalIncident = 'medical_incident';
    case DisciplinaryExclusion = 'disciplinary_exclusion';

    public function label(): string
    {
        return match ($this) {
            self::EnvelopeOpening => __('محضر فتح وتأكيد سلامة أظرفة المواضيع'),
            self::CheatingAttempt => __('محضر إثبات محاولة غش (المرسوم 20-241)'),
            self::CandidateAbsence => __('محضر تسجيل غياب مترشح عن الجلسة'),
            self::MedicalIncident => __('محضر حالة صحية طارئة وإسعاف'),
            self::DisciplinaryExclusion => __('محضر إقصاء انضباطي وإخلال بالنظام'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::EnvelopeOpening => 'info',
            self::CheatingAttempt => 'danger',
            self::CandidateAbsence => 'warning',
            self::MedicalIncident => 'gray',
            self::DisciplinaryExclusion => 'danger',
        };
    }
}

