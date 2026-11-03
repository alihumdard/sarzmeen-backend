<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\My;

use App\Http\Controllers\Controller;
use App\Models\SavedPaymentPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Payment plans a buyer worked out on the calculator and kept.
 *
 * Every query is scoped to the signed-in user rather than bound from the
 * route, so one person's id can never reach another person's plan.
 */
class SavedPaymentPlanController extends Controller
{
    /** Plenty for the calculator's state, small enough to not be a dumping ground. */
    private const MAX_INPUT_BYTES = 20000;

    public function index(Request $request): JsonResponse
    {
        $plans = SavedPaymentPlan::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $plans->map(fn ($p) => $this->present($p, false))->all(),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->present($this->find($request, $id))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePlan($request);

        $plan = SavedPaymentPlan::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'inputs' => $data['inputs'],
            'total_price' => $data['totalPrice'] ?? 0,
            'net_total' => $data['netTotal'] ?? 0,
        ]);

        return response()->json(['data' => $this->present($plan)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $plan = $this->find($request, $id);
        $data = $this->validatePlan($request);

        $plan->update([
            'title' => $data['title'],
            'inputs' => $data['inputs'],
            'total_price' => $data['totalPrice'] ?? 0,
            'net_total' => $data['netTotal'] ?? 0,
        ]);

        return response()->json(['data' => $this->present($plan)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->find($request, $id)->delete();

        return response()->json(null, 204);
    }

    /** 404 for someone else's plan, which says no more than "not yours". */
    private function find(Request $request, int $id): SavedPaymentPlan
    {
        return SavedPaymentPlan::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();
    }

    private function validatePlan(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'inputs' => ['required', 'array'],
            'totalPrice' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'netTotal' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
        ]);

        /*
         * The calculator's state is stored as-is, so bound it here: without
         * a ceiling the column is an open write target for anything that
         * fits in a JSON array.
         */
        if (strlen((string) json_encode($data['inputs'])) > self::MAX_INPUT_BYTES) {
            abort(422, 'That plan is too large to save.');
        }

        return $data;
    }

    private function present(SavedPaymentPlan $plan, bool $withInputs = true): array
    {
        return [
            'id' => (string) $plan->id,
            'title' => $plan->title,
            'totalPrice' => (float) $plan->total_price,
            'netTotal' => (float) $plan->net_total,
            'savedAt' => $plan->created_at?->toIso8601String(),
            'updatedAt' => $plan->updated_at?->toIso8601String(),
            // The list does not need the state; only opening one does.
            ...($withInputs ? ['inputs' => $plan->inputs] : []),
        ];
    }
}
