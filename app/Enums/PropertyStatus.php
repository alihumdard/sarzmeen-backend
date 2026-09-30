<?php

declare(strict_types=1);

namespace App\Enums;

enum PropertyStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Sold = 'sold';
}
