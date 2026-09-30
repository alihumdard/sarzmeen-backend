<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqRequest;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class FaqController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $faqs = Faq::orderBy('order')->get();

        return FaqResource::collection($faqs);
    }

    public function store(FaqRequest $request): JsonResponse
    {
        $faq = Faq::create($request->validated());

        $this->clearCache();

        return (new FaqResource($faq))
            ->response()
            ->setStatusCode(201);
    }

    public function update(FaqRequest $request, Faq $faq): FaqResource
    {
        $faq->update($request->validated());

        $this->clearCache();

        return new FaqResource($faq);
    }

    public function destroy(Faq $faq): JsonResponse
    {
        $faq->delete();

        $this->clearCache();

        return response()->json(null, 204);
    }

    private function clearCache(): void
    {
        Cache::forget('public_faqs');
    }
}
