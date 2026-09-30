<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class FaqController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $faqs = Cache::remember('public_faqs', 300, function () {
            return Faq::published()
                ->orderBy('order')
                ->get();
        });

        return FaqResource::collection($faqs);
    }
}
