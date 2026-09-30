<?php

declare(strict_types=1);

namespace App\Enums;

enum PropertyPurpose: string
{
    case Sale = 'sale';
    case Rent = 'rent';
}
