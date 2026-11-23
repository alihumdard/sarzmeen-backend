<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocietyMapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = app(ImageService::class);

        $image = $images->url($this->image ?? '');
        $document = $this->document ? $images->url($this->document) : null;

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'image' => $image,

            /* What the download button hands over. A map is often published
               as a PDF as well as a picture; where there is no PDF the
               picture itself is the download, so the button always works. */
            'download' => $document ?? $image,
            'hasDocument' => $document !== null,

            'society' => $this->whenLoaded('location', fn () => [
                'id' => (string) $this->location->id,
                'name' => $this->location->name,
                'slug' => $this->location->slug,
            ]),

            'city' => $this->whenLoaded('city', fn () => $this->city ? [
                'id' => (string) $this->city->id,
                'name' => $this->city->name,
                'slug' => $this->city->slug,
            ] : null),

            'featured' => (bool) $this->featured,
            'views' => (int) $this->views,
            'downloads' => (int) $this->downloads,
            'publishedAt' => $this->published_at?->toDateString(),

            /* Only the single-map page asks for the write-up; a listing of
               thirty would otherwise carry thirty articles of HTML. The
               caller says which it wants by loading `blogs` or not. */
            $this->mergeWhen($this->resource->relationLoaded('blogs'), fn () => [
                'description' => $this->description,
            ]),

            'blogs' => BlogResource::collection($this->whenLoaded('blogs')),

            'metaTitle' => $this->meta_title,
            'metaDescription' => $this->meta_description,
        ];
    }
}
