<?php

declare(strict_types=1);

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case Pending = 'pending';
    case Contacted = 'contacted';
    case Closed = 'closed';
}
