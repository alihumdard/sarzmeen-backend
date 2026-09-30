<?php

declare(strict_types=1);

namespace App\Enums;

enum ConditionStatus: string
{
    case Ready = 'ready';
    case UnderConstruction = 'under_construction';
    case AvailableNow = 'available_now';
}
