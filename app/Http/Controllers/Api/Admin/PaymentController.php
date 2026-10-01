<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Payment::with(['user', 'subscription.plan'])
            ->latest();

        if ($request->filled('status')) {
            $status = PaymentStatus::tryFrom($request->input('status'));
            if ($status) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        return PaymentResource::collection(
            $query->paginate($request->integer('per_page', 20)),
        );
    }

    public function show(Payment $payment): PaymentResource
    {
        $payment->load(['user', 'subscription.plan']);

        return new PaymentResource($payment);
    }

    public function updateStatus(Request $request, Payment $payment): PaymentResource
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(PaymentStatus::class)],
            'transactionId' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment->update([
            'status' => $validated['status'],
            'transaction_id' => $validated['transactionId'] ?? $payment->transaction_id,
            'notes' => $validated['notes'] ?? $payment->notes,
        ]);

        if ($validated['status'] === PaymentStatus::Completed->value && $payment->subscription) {
            $subscription = $payment->subscription;
            if ($subscription->status !== SubscriptionStatus::Active) {
                $subscription->update([
                    'status' => SubscriptionStatus::Active,
                    'starts_at' => now(),
                    'expires_at' => now()->addDays($subscription->plan->duration_days),
                ]);
            }
        }

        $payment->load(['user', 'subscription.plan']);

        return new PaymentResource($payment);
    }
}
