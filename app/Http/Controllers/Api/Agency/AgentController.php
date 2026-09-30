<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agency;

use App\Actions\Agency\InviteAgent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\InviteAgentRequest;
use App\Http\Resources\AgentResource;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        if (! $agency) {
            return response()->json(['data' => []], 200);
        }

        $agents = $agency->agents()
            ->with(['user', 'specializations', 'languages'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return AgentResource::collection($agents)->response();
    }

    public function store(InviteAgentRequest $request, InviteAgent $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->validated());

        return (new AgentResource($user->agent->load(['user', 'agency', 'specializations', 'languages'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Agent $agent): JsonResponse
    {
        $agency = $request->user()->agency;

        if (! $agency || $agent->agency_id !== $agency->id) {
            abort(403, 'You can only manage your own team members.');
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'office_address' => ['nullable', 'string', 'max:255'],
        ]);

        $agent->update($validated);

        return (new AgentResource($agent->load(['user', 'agency', 'specializations', 'languages'])))
            ->response();
    }

    public function destroy(Request $request, Agent $agent): JsonResponse
    {
        $agency = $request->user()->agency;

        if (! $agency || $agent->agency_id !== $agency->id) {
            abort(403, 'You can only manage your own team members.');
        }

        $agent->user->delete();
        $agent->delete();

        return response()->json(['message' => 'Agent removed from team']);
    }
}
