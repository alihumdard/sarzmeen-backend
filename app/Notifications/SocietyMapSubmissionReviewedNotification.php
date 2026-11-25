<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SocietyMapSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells whoever sent a map what became of it.
 *
 * Without this the sender hears nothing at all: they are usually not
 * users of the site, so there is no account for them to check, and a
 * rejection is useless to them unless they learn why.
 */
class SocietyMapSubmissionReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly SocietyMapSubmission $submission,
        /** The slug of the map it became, where it was published. */
        private readonly ?string $mapSlug = null,
    ) {}

    public function via(object $notifiable): array
    {
        /* A sender who gave no account has no database to write to. */
        return $notifiable instanceof AnonymousNotifiable
            ? ['mail']
            : ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $published = $this->mapSlug !== null;
        $base = rtrim((string) config('app.frontend_url'), '/');

        return (new MailMessage)
            ->subject(
                $published
                    ? 'Your society map is live - '.config('app.name')
                    : 'About the map you sent us - '.config('app.name')
            )
            ->markdown('mail.society-map.reviewed', [
                'submission' => $this->submission,
                'published' => $published,
                'mapUrl' => $published ? "{$base}/society-maps/{$this->mapSlug}" : null,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'society_map_reviewed',
            'submission_id' => $this->submission->id,
            'society' => $this->submission->society_name,
            'status' => $this->submission->status->value,
        ];
    }
}
