<?php

declare(strict_types=1);

namespace App\Enums;

enum LocationType: string
{
    case City = 'city';
    case Area = 'area';
    case Society = 'society';
}
