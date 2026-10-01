<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InquiryRequest;
use App\Http\Resources\InquiryResource;
use App\Models\Inquiry;
use App\Models\User;
use App\Notifications\InquiryReceivedNotification;
use Illuminate\Http\JsonResponse;

class InquiryController extends Controller
{
    public function store(InquiryRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
        }

        $data['source'] = 'website';

        $inquiry = Inquiry::create($data);

        $inquiry->load('property');

        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new InquiryReceivedNotification($inquiry));
        }

        if ($inquiry->property?->owner) {
            $inquiry->property->owner->notify(new InquiryReceivedNotification($inquiry));
        }

        return (new InquiryResource($inquiry))
            ->response()
            ->setStatusCode(201);
    }
}
