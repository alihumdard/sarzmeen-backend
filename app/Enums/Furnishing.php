<?php

declare(strict_types=1);

namespace App\Enums;

enum Furnishing: string
{
    case Furnished = 'furnished';
    case Semi = 'semi';
    case Unfurnished = 'unfurnished';
}
