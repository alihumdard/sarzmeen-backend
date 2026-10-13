<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectFormat: string
{
    case HighRise = 'high_rise';
    case MidRise = 'mid_rise';
    case LowRise = 'low_rise';
    case Mall = 'mall';
    case Plaza = 'plaza';
    case Villas = 'villas';
    case HousingCommunity = 'housing_community';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::HighRise => 'High-Rise',
            self::MidRise => 'Mid-Rise',
            self::LowRise => 'Low-Rise',
            self::Mall => 'Mall',
            self::Plaza => 'Plaza',
            self::Villas => 'Villas',
            self::HousingCommunity => 'Housing Community',
            self::Other => 'Other',
        };
    }
}
