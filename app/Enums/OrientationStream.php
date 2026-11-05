<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OrientationStream: string implements HasColor, HasIcon, HasLabel
{
    case ScienceTech = 'science_tech';
    case ArtsHumanities = 'arts_humanities';
    case VocationalTraining = 'vocational_training';
    case RepeatYear = 'repeat_year';

    public function label(): string
    {
        return match ($this) {
            self::ScienceTech => __('جذع مشترك علوم وتكنولوجيا'),
            self::ArtsHumanities => __('جذع مشترك آداب'),
            self::VocationalTraining => __('توجيه نحو التكوين والتعليم المهنيين'),
            self::RepeatYear => __('إعادة السنة الرابعة متوسط'),
        };
    }

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::ScienceTech => __('ج.م علوم وتكنولوجيا'),
            self::ArtsHumanities => __('ج.م آداب'),
            self::VocationalTraining => __('تكوين مهني'),
            self::RepeatYear => __('إعادة السنة'),
        };
    }

    public function code(): string
    {
        return match ($this) {
            self::ScienceTech => 'TC-ST',
            self::ArtsHumanities => 'TC-L',
            self::VocationalTraining => 'FP',
            self::RepeatYear => 'RED',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ScienceTech => 'info',
            self::ArtsHumanities => 'warning',
            self::VocationalTraining => 'success',
            self::RepeatYear => 'danger',
        };
    }

    public function getColor(): ?string
    {
        return $this->color();
    }

    public function icon(): string
    {
        return match ($this) {
            self::ScienceTech => 'heroicon-o-beaker',
            self::ArtsHumanities => 'heroicon-o-book-open',
            self::VocationalTraining => 'heroicon-o-wrench-screwdriver',
            self::RepeatYear => 'heroicon-o-arrow-path',
        };
    }

    public function getIcon(): ?string
    {
        return $this->icon();
    }
}
