<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectType: string
{
    case Residential = 'residential';
    case Commercial = 'commercial';
    case MixedUse = 'mixed_use';

    public function label(): string
    {
        return match ($this) {
            self::Residential => 'Residential',
            self::Commercial => 'Commercial',
            self::MixedUse => 'Mixed Use',
        };
    }
}
