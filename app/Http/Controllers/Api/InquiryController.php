<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\InquiryRequest;
use App\Http\Resources\InquiryResource;
use App\Models\Inquiry;
use App\Models\User;
use App\Notifications\InquiryConfirmationNotification;
use App\Notifications\InquiryReceivedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class InquiryController extends Controller
{
    public function store(InquiryRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
        }

        $data['source'] = 'website';
        // Set explicitly: the column default is not applied to the new instance.
        $data['status'] = InquiryStatus::New->value;

        $inquiry = Inquiry::create($data);

        $inquiry->load(['property.owner', 'project']);

        /*
         * The lead is already saved at this point, so a mail failure must not
         * fail the request — the user would see an error for a successful
         * submission and likely submit again.
         */
        try {
            $recipients = User::role('admin')->get();

            if ($owner = $inquiry->property?->owner) {
                // Avoid double-sending when the owner is also an admin.
                if (! $recipients->contains('id', $owner->id)) {
                    $recipients->push($owner);
                }
            }

            foreach ($recipients as $recipient) {
                $recipient->notify(new InquiryReceivedNotification($inquiry));
            }

            Notification::route('mail', $inquiry->email)
                ->notify(new InquiryConfirmationNotification($inquiry));
        } catch (\Throwable $e) {
            Log::error('Inquiry notification failed', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }

        return (new InquiryResource($inquiry))
            ->response()
            ->setStatusCode(201);
    }
}
