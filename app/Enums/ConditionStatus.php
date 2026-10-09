<?php

declare(strict_types=1);

namespace App\Enums;

enum ConditionStatus: string
{
    case BrandNew = 'brand_new';
    case LikeNew = 'like_new';
    case Used = 'used';
    case UnderConstruction = 'under_construction';

    public function label(): string
    {
        return match ($this) {
            self::BrandNew => 'Brand New',
            self::LikeNew => 'Like New',
            self::Used => 'Used',
            self::UnderConstruction => 'Under Construction',
        };
    }
}
