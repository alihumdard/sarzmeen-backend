<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentStatus: string
{
    case Published = 'published';
    case Hidden = 'hidden';
}
