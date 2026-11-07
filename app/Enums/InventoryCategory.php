<?php

namespace App\Enums;

enum InventoryCategory: string
{
    case Furniture = 'furniture';
    case ScientificLab = 'scientific_lab';
    case AudiovisualIt = 'audiovisual_it';
    case Sports = 'sports';
    case LibraryDocs = 'library_docs';
    case KitchenRefectory = 'kitchen_refectory';

    public function label(): string
    {
        return match ($this) {
            self::Furniture => __('أثاث مدرسي ومكتبي'),
            self::ScientificLab => __('عتاد علمي ومخبري'),
            self::AudiovisualIt => __('أجهزة إعلام آلي وسمعي بصري'),
            self::Sports => __('عتاد وأدوات التربية البدنية'),
            self::LibraryDocs => __('كتب ومراجع ومخطوطات التوثيق'),
            self::KitchenRefectory => __('عتاد المطعم والداخلية'),
        };
    }

    public function code(): string
    {
        return match ($this) {
            self::Furniture => '01',
            self::ScientificLab => '02',
            self::AudiovisualIt => '03',
            self::Sports => '04',
            self::LibraryDocs => '05',
            self::KitchenRefectory => '06',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Furniture => 'primary',
            self::ScientificLab => 'success',
            self::AudiovisualIt => 'info',
            self::Sports => 'warning',
            self::LibraryDocs => 'gray',
            self::KitchenRefectory => 'danger',
        };
    }
}
