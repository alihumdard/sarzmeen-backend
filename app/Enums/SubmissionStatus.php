<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a submitted map stands.
 *
 * Separate from a published map's own draft/published states: a
 * submission is somebody else's offer, which is waiting, accepted or
 * turned down.
 */
enum SubmissionStatus: string
{
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In review',
            self::Published => 'Published',
            self::Rejected => 'Rejected',
        };
    }
}
