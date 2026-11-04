<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgencyResource;
use App\Models\Agency;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * An agency's own profile, including the logo shown on its public page.
 *
 * The logo column and the public page that displays it both existed, but
 * nothing could ever set it, so every agency showed its initial in a grey
 * circle with no way to replace it.
 */
class ProfileController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => (new AgencyResource($this->agency($request)))->resolve($request),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $agency = $this->agency($request);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'establishedYear' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            /*
             * Upload before removing the old file: deleting first means a
             * failed upload takes the logo with it and leaves the record
             * pointing at a file that is no longer there.
             */
            $previous = $agency->logo;

            $agency->logo = $this->imageService->upload(
                $request->file('logo'),
                'agencies',
                600,
            );

            if ($previous && $previous !== $agency->logo) {
                $this->imageService->delete($previous);
            }
        }

        foreach ([
            'name' => 'name',
            'city' => 'city',
            'address' => 'address',
            'description' => 'description',
            'phone' => 'phone',
            'email' => 'email',
            'website' => 'website',
            'establishedYear' => 'established_year',
        ] as $input => $column) {
            if (array_key_exists($input, $data)) {
                $agency->{$column} = $data[$input];
            }
        }

        $agency->save();
        $agency->loadCount('agents');

        return response()->json([
            'data' => (new AgencyResource($agency))->resolve($request),
        ]);
    }

    /** The signed-in account's own agency; 404 rather than someone else's. */
    private function agency(Request $request): Agency
    {
        return Agency::where('user_id', $request->user()->id)
            ->withCount('agents')
            ->firstOrFail();
    }
}
