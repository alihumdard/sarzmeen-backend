<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a video stands.
 *
 * Videos shared the blog statuses, which have no word for "waiting to be
 * looked at" — an owner's video goes up for review rather than straight
 * onto their page, so it needs one.
 *
 * The three the blogs had keep their values, so every row already in the
 * table reads the same as before.
 */
enum VideoStatus: string
{
    /** The owner is still writing it; nobody else sees it. */
    case Draft = 'draft';

    /** Submitted, waiting for an administrator. */
    case Pending = 'pending';

    /** Live on the owner's page. */
    case Published = 'published';

    /** Turned down, with a reason the owner can read. */
    case Rejected = 'rejected';

    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'In review',
            self::Published => 'Published',
            self::Rejected => 'Rejected',
            self::Archived => 'Archived',
        };
    }
}
