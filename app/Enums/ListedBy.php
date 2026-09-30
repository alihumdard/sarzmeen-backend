<?php

declare(strict_types=1);

namespace App\Enums;

enum ListedBy: string
{
    case Owner = 'owner';
    case Agent = 'agent';
    case Agency = 'agency';
}
