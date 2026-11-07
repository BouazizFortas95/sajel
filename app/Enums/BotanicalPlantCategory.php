<?php

namespace App\Enums;

enum BotanicalPlantCategory: string
{
    case AromaticMedicinal = 'aromatic_medicinal';
    case VegetableCrop = 'vegetable_crop';
    case FruitTree = 'fruit_tree';
    case OrnamentalFlowering = 'ornamental_flowering';
    case ForestShrub = 'forest_shrub';

    public function label(): string
    {
        return match ($this) {
            self::AromaticMedicinal => __('نباتات عطرية وطبية (Plantes aromatiques et médicinales)'),
            self::VegetableCrop => __('خضروات ومحاصيل بيداغوجية (Cultures maraîchères)'),
            self::FruitTree => __('أشجار مثمرة وحمضيات (Arbres fruitiers)'),
            self::OrnamentalFlowering => __('أزهار ونباتات تزيين (Plantes ornementales et florales)'),
            self::ForestShrub => __('شجيرات حراجية وغابية (Arbustes forestiers et brise-vent)'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::AromaticMedicinal => 'success',
            self::VegetableCrop => 'warning',
            self::FruitTree => 'primary',
            self::OrnamentalFlowering => 'danger',
            self::ForestShrub => 'info',
        };
    }
}
