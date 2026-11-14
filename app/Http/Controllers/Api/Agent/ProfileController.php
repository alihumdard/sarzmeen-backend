<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * An agent's own profile.
 *
 * An agency could already write its own description; an agent could not.
 * The bio column, the years and deals figures and the public page that
 * shows all three existed, but only an administrator could ever fill them
 * in, so almost every agent's page carried an empty card.
 *
 * The photograph lives on the user record rather than the agent one, so it
 * is saved there while the rest is saved here.
 */
class ProfileController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => (new AgentResource($this->agent($request)))->resolve($request),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $agent = $this->agent($request);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'yearsExperience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'dealsClosed' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'officeAddress' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $user = $agent->user;

        if ($request->hasFile('avatar')) {
            /*
             * Upload before removing the old file: deleting first means a
             * failed upload takes the photograph with it and leaves the
             * record pointing at a file that is no longer there.
             */
            $previous = $user->avatar;

            $user->avatar = $this->imageService->upload(
                $request->file('avatar'),
                'avatars',
                600,
            );

            if ($previous && $previous !== $user->avatar) {
                $this->imageService->delete($previous);
            }
        }

        foreach (['name' => 'name', 'phone' => 'phone'] as $input => $column) {
            if (array_key_exists($input, $data)) {
                $user->{$column} = $data[$input];
            }
        }

        $user->save();

        foreach ([
            'title' => 'title',
            'bio' => 'bio',
            'yearsExperience' => 'years_experience',
            'dealsClosed' => 'deals_closed',
            'officeAddress' => 'office_address',
        ] as $input => $column) {
            if (array_key_exists($input, $data)) {
                $agent->{$column} = $data[$input];
            }
        }

        $agent->save();

        return response()->json([
            'data' => (new AgentResource($this->agent($request)))->resolve($request),
        ]);
    }

    /**
     * The signed-in account's own agent record; 404 rather than someone
     * else's. Verification and agency are deliberately not writable here:
     * an agent marking themselves verified would make the tick meaningless.
     */
    private function agent(Request $request): Agent
    {
        return Agent::where('user_id', $request->user()->id)
            ->with(['user', 'agency', 'specializations', 'languages'])
            ->firstOrFail();
    }
}
