<?php

declare(strict_types=1);

namespace App\Enums;

enum UnitAvailability: string
{
    case Available = 'available';
    case Limited = 'limited';
    case SoldOut = 'sold_out';
    case ComingSoon = 'coming_soon';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Limited => 'Limited',
            self::SoldOut => 'Sold Out',
            self::ComingSoon => 'Coming Soon',
        };
    }
}
