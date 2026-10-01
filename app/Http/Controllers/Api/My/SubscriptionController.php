<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\My;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\UserSubscriptionResource;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function plans(): AnonymousResourceCollection
    {
        $plans = SubscriptionPlan::active()
            ->orderBy('sort_order')
            ->get();

        return SubscriptionPlanResource::collection($plans);
    }

    public function current(Request $request): JsonResponse
    {
        $subscription = $request->user()->activeSubscription();

        return response()->json([
            'data' => $subscription
                ? new UserSubscriptionResource($subscription)
                : null,
        ]);
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $subscriptions = $request->user()
            ->subscriptions()
            ->with('plan')
            ->latest()
            ->paginate(10);

        return UserSubscriptionResource::collection($subscriptions);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'planId' => ['required', 'exists:subscription_plans,id'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'transactionId' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $plan = SubscriptionPlan::findOrFail($validated['planId']);

        abort_unless($plan->is_active, 422, 'This plan is no longer available.');

        $existing = $user->activeSubscription();
        if ($existing) {
            return response()->json([
                'message' => 'You already have an active subscription. Wait until it expires or cancel it first.',
            ], 422);
        }

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Pending,
        ]);

        $payment = Payment::create([
            'reference' => 'PAY-' . strtoupper(Str::random(10)),
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => $plan->price,
            'method' => $validated['method'],
            'status' => PaymentStatus::Pending,
            'transaction_id' => $validated['transactionId'] ?? null,
        ]);

        $subscription->load('plan');

        return response()->json([
            'data' => [
                'subscription' => new UserSubscriptionResource($subscription),
                'payment' => new PaymentResource($payment),
            ],
            'message' => 'Subscription created. Awaiting payment verification.',
        ], 201);
    }

    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->activeSubscription();

        if (! $subscription) {
            return response()->json(['message' => 'No active subscription to cancel.'], 422);
        }

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return response()->json(['message' => 'Subscription cancelled.']);
    }

    public function payments(Request $request): AnonymousResourceCollection
    {
        $payments = $request->user()
            ->payments()
            ->with(['subscription.plan'])
            ->latest()
            ->paginate(15);

        return PaymentResource::collection($payments);
    }
}
