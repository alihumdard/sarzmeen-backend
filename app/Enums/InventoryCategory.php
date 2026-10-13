<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Which set of detail fields a unit carries, per specification sections 6-8.
 */
enum InventoryCategory: string
{
    case Residential = 'residential';
    case Commercial = 'commercial';
    case Hospitality = 'hospitality';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Residential => 'Residential',
            self::Commercial => 'Commercial',
            self::Hospitality => 'Hospitality',
            self::Other => 'Other',
        };
    }
}
