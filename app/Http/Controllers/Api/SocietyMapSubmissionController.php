<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SocietyMapSubmissionRequest;
use App\Models\SocietyMapSubmission;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;

/**
 * Where a society owner offers their map.
 *
 * Public, because the people who hold these maps — society offices,
 * developers, their dealers — are not users of the site and will not
 * register to send one in.
 */
class SocietyMapSubmissionController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
    ) {}

    public function store(SocietyMapSubmissionRequest $request): JsonResponse
    {
        $data = $request->validated();

        /* No resize: the detail a reader zooms in for is exactly what
           shrinking throws away, and an administrator should see what
           was actually sent. */
        $image = $this->images->upload(
            $request->file('imageFile'),
            'society-map-submissions',
            null,
        );

        $document = $request->hasFile('documentFile')
            ? $this->images->upload(
                $request->file('documentFile'),
                'society-map-submissions',
                null,
            )
            : null;

        /* Resized, unlike the map: a face on an admin screen needs no
           more than this, and the original would sit on disk forever. */
        $photo = $request->hasFile('photoFile')
            ? $this->images->upload(
                $request->file('photoFile'),
                'society-map-submissions',
                400,
            )
            : null;

        $submission = SocietyMapSubmission::create([
            'society_name' => $data['societyName'],
            'city_name' => $data['cityName'] ?? null,
            'noc_approved' => $data['nocApproved'] ?? null,
            'noc_authority' => $data['nocAuthority'] ?? null,
            'contact_name' => $data['contactName'],
            'contact_phone' => $data['contactPhone'],
            'contact_email' => $data['contactEmail'] ?? null,
            'contact_role' => $data['contactRole'] ?? null,
            'contact_photo' => $photo,
            'user_id' => $request->user()?->id,
            'notes' => $data['notes'] ?? '',
            'image' => $image,
            'document' => $document,
            'status' => SubmissionStatus::Pending->value,
            'submitted_ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Thank you — your map has been sent for review. We will be in touch on the number you gave.',
            'reference' => 'SM-'.str_pad((string) $submission->id, 5, '0', STR_PAD_LEFT),
        ], 201);
    }
}
