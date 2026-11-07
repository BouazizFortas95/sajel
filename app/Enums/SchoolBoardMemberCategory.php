<?php

namespace App\Enums;

enum SchoolBoardMemberCategory: string
{
    case Statutory = 'statutory';
    case ElectedTeacher = 'elected_teacher';
    case ElectedStaff = 'elected_staff';
    case ElectedParent = 'elected_parent';

    public function label(): string
    {
        return match ($this) {
            self::Statutory => __('عضو بحكم المنصب (Membre de droit)'),
            self::ElectedTeacher => __('أستاذ منتخب (Enseignant élu)'),
            self::ElectedStaff => __('موظف / عامل منتخب (Personnel administratif / ouvrier élu)'),
            self::ElectedParent => __('ممثل أولياء التلاميذ (Représentant des parents d\'élèves)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Statutory => 'primary',
            self::ElectedTeacher => 'success',
            self::ElectedStaff => 'warning',
            self::ElectedParent => 'info',
        };
    }
}

