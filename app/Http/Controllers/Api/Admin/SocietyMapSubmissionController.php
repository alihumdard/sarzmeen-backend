<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\SocietyMap;
use App\Models\SocietyMapSubmission;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Reviewing the maps society owners have sent in.
 */
class SocietyMapSubmissionController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $submissions = SocietyMapSubmission::with(['location', 'map'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = SubmissionStatus::tryFrom($request->string('status')->toString());
                if ($status) {
                    $q->where('status', $status->value);
                }
            })
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => collect($submissions->items())->map(
                fn (SocietyMapSubmission $s) => $this->shape($s)
            )->all(),
            'meta' => [
                'total' => $submissions->total(),
                'current_page' => $submissions->currentPage(),
                'last_page' => $submissions->lastPage(),
                /* So the sidebar can show how many are waiting. */
                'pending' => SocietyMapSubmission::where('status', SubmissionStatus::Pending->value)->count(),
            ],
        ]);
    }

    /**
     * Turn a submission into a published map.
     *
     * The administrator supplies the things the sender could not: which
     * location this really is, and the title and write-up the page will
     * carry.
     */
    public function approve(Request $request, SocietyMapSubmission $submission): JsonResponse
    {
        if ($submission->status !== SubmissionStatus::Pending) {
            return response()->json([
                'message' => 'That submission has already been reviewed.',
            ], 422);
        }

        $data = $request->validate([
            'locationId' => ['required', 'integer', 'exists:locations,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
        ]);

        if (SocietyMap::where('location_id', $data['locationId'])->exists()) {
            return response()->json([
                'message' => 'That society already has a map. Edit the existing one instead.',
            ], 422);
        }

        $location = Location::find($data['locationId']);

        /* Copy rather than move: the submission keeps its own files, so
           the record of what was sent survives any later edit of the
           published map. Given a fresh name, since the uploaded one
           could collide with a file already published. */
        $image = $this->copyToLibrary($submission->image);
        $document = $submission->document
            ? $this->copyToLibrary($submission->document)
            : null;

        $map = SocietyMap::create([
            'location_id' => $data['locationId'],
            'city_id' => SocietyMap::resolveCity($location)?->id,
            'title' => $data['title'],
            'slug' => SocietyMap::uniqueSlug($data['title']),
            'image' => $image,
            'document' => $document,
            'summary' => $data['summary'] ?? $submission->notes,
            'description' => $data['description'] ?? '',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $submission->update([
            'status' => SubmissionStatus::Published->value,
            'location_id' => $data['locationId'],
            'society_map_id' => $map->id,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Published.',
            'mapSlug' => $map->slug,
        ]);
    }

    public function reject(Request $request, SocietyMapSubmission $submission): JsonResponse
    {
        if ($submission->status !== SubmissionStatus::Pending) {
            return response()->json([
                'message' => 'That submission has already been reviewed.',
            ], 422);
        }

        $data = $request->validate([
            /* Required: somebody is waiting to hear why. */
            'note' => ['required', 'string', 'max:500'],
        ]);

        $submission->update([
            'status' => SubmissionStatus::Rejected->value,
            'review_note' => $data['note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['message' => 'Submission rejected.']);
    }

    public function destroy(SocietyMapSubmission $submission): JsonResponse
    {
        /* The published map, if there is one, keeps its own copies. */
        if ($submission->image) {
            $this->images->delete($submission->image);
        }

        if ($submission->document) {
            $this->images->delete($submission->document);
        }

        if ($submission->contact_photo) {
            $this->images->delete($submission->contact_photo);
        }

        $submission->delete();

        return response()->json(['message' => 'Submission deleted.']);
    }

    /**
     * Copy a submitted file into the published library under a new name.
     */
    private function copyToLibrary(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'bin';
        $target = 'society-maps/'.\Illuminate\Support\Str::random(30).'.'.$extension;

        Storage::disk('public')->copy($path, $target);

        return $target;
    }

    private function shape(SocietyMapSubmission $s): array
    {
        return [
            'id' => (string) $s->id,
            'reference' => 'SM-'.str_pad((string) $s->id, 5, '0', STR_PAD_LEFT),
            'societyName' => $s->society_name,
            'cityName' => $s->city_name,
            'nocApproved' => $s->noc_approved,
            'nocAuthority' => $s->noc_authority,
            'contact' => [
                'name' => $s->contact_name,
                'phone' => $s->contact_phone,
                'email' => $s->contact_email,
                'role' => $s->contact_role,
                'photo' => $s->contact_photo ? $this->images->url($s->contact_photo) : null,
            ],
            'notes' => $s->notes,
            'image' => $this->images->url($s->image),
            'document' => $s->document ? $this->images->url($s->document) : null,
            'status' => $s->status->value,
            'statusLabel' => $s->status->label(),
            'reviewNote' => $s->review_note,
            'mapSlug' => $s->map?->slug,
            'submittedAt' => $s->created_at?->toDateTimeString(),
        ];
    }
}
