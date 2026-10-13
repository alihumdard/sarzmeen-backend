<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Stored as the display label rather than a slug, because existing rows and
 * the public filters already match on these exact strings.
 */
enum ProjectStatus: string
{
    case PreLaunch = 'Pre-Launch';
    case BookingOpen = 'Booking Open';
    case UnderConstruction = 'Under Construction';
    case NearCompletion = 'Near Completion';
    case ReadyToMove = 'Ready to Move';
    case PossessionAvailable = 'Possession Available';
    case Completed = 'Completed';
}
