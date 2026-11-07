<?php

namespace App\Enums;

enum SchoolBoardSessionStatus: string
{
    case Draft = 'draft';
    case Deliberated = 'deliberated';
    case SubmittedToDirectorate = 'submitted_to_directorate';
    case Approved = 'approved';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('مسودة محضر (Projet de PV)'),
            self::Deliberated => __('تمت المداولة والمصادقة (Délibéré & Signé)'),
            self::SubmittedToDirectorate => __('مرسل لمديرية التربية (Transmis à la Direction)'),
            self::Approved => __('مؤشر ومصادق عليه رسمياً (Visé et Approuvé)'),
            self::Archived => __('مؤرشف ومختتم (Archivé)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Deliberated => 'primary',
            self::SubmittedToDirectorate => 'warning',
            self::Approved => 'success',
            self::Archived => 'gray',
        };
    }
}

