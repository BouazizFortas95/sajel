<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InfractionDegree: string implements HasColor, HasLabel
{
    case FirstDegree = 'first_degree';
    case SecondDegree = 'second_degree';
    case ThirdDegree = 'third_degree';

    public function label(): string
    {
        return match ($this) {
            self::FirstDegree => __('الدرجة الأولى (مخالفات انضباطية وسلوكية بسيطة)'),
            self::SecondDegree => __('الدرجة الثانية (عرقلة الدروس، الشجار، إتلاف وثائق)'),
            self::ThirdDegree => __('الدرجة الثالثة (اعتداء، إتلاف خطير، حيازة ممنوعات)'),
        };
    }

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::FirstDegree => __('الدرجة الأولى'),
            self::SecondDegree => __('الدرجة الثانية'),
            self::ThirdDegree => __('الدرجة الثالثة'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FirstDegree => 'amber',
            self::SecondDegree => 'orange',
            self::ThirdDegree => 'rose',
        };
    }

    public function getColor(): string|array|null
    {
        return $this->color();
    }
}
