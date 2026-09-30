<?php

declare(strict_types=1);

namespace App\Enums;

enum NearbyPlaceKind: string
{
    case Park = 'park';
    case Road = 'road';
    case Airport = 'airport';
    case Mall = 'mall';
}
