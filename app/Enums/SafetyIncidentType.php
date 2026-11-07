<?php

namespace App\Enums;

enum SafetyIncidentType: string
{
    case StudentAccident = 'student_accident';
    case WorkAccident = 'work_accident';
    case InfrastructureHazard = 'infrastructure_hazard';
    case MaterialDamage = 'material_damage';

    public function label(): string
    {
        return match ($this) {
            self::StudentAccident => __('حادث مدرسي لتلميذ (Accident scolaire)'),
            self::WorkAccident => __('حادث عمل لموظف (Accident de travail)'),
            self::InfrastructureHazard => __('خطر هيكلي أو خلل بالمنشأة (Risque bâtiment)'),
            self::MaterialDamage => __('أضرار مادية وتلف تجهيزات (Dégâts matériels)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::StudentAccident => 'danger',
            self::WorkAccident => 'warning',
            self::InfrastructureHazard => 'info',
            self::MaterialDamage => 'gray',
        };
    }
}
