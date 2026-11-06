<?php

namespace App\Enums;

enum FoodItemCategory: string
{
    case MeatFish = 'meat_fish';
    case Produce = 'produce';
    case DryGoods = 'dry_goods';
    case Dairy = 'dairy';
    case Bakery = 'bakery';
    case OilsCondiments = 'oils_condiments';

    public function label(): string
    {
        return match ($this) {
            self::MeatFish => __('لحوم ودواجن وأسماك'),
            self::Produce => __('خضر وفواكه طازجة'),
            self::DryGoods => __('بقوليات وبقالة وحبوب'),
            self::Dairy => __('ألبان وأجبان وبيض'),
            self::Bakery => __('مخبوزات وعجائن'),
            self::OilsCondiments => __('زيوت وتوابل ومعلبات'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MeatFish => 'danger',
            self::Produce => 'success',
            self::DryGoods => 'warning',
            self::Dairy => 'info',
            self::Bakery => 'primary',
            self::OilsCondiments => 'gray',
        };
    }
}

