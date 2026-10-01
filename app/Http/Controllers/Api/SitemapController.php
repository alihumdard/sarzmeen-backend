<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\BlogStatus;
use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Project;
use App\Models\Property;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $frontendUrl = config('app.frontend_url', 'https://sarzameen.com');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $staticPages = ['', '/properties', '/projects', '/blogs', '/contact'];
        foreach ($staticPages as $page) {
            $xml .= $this->urlEntry("{$frontendUrl}{$page}", now()->toDateString(), 'daily', '1.0');
        }

        Property::where('status', PropertyStatus::Published)
            ->select(['slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->chunk(500, function ($properties) use (&$xml, $frontendUrl) {
                foreach ($properties as $property) {
                    $xml .= $this->urlEntry(
                        "{$frontendUrl}/properties/{$property->slug}",
                        $property->updated_at->toDateString(),
                        'weekly',
                        '0.8',
                    );
                }
            });

        Project::select(['slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->chunk(500, function ($projects) use (&$xml, $frontendUrl) {
                foreach ($projects as $project) {
                    $xml .= $this->urlEntry(
                        "{$frontendUrl}/projects/{$project->slug}",
                        $project->updated_at->toDateString(),
                        'weekly',
                        '0.7',
                    );
                }
            });

        Blog::where('status', BlogStatus::Published)
            ->select(['slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->chunk(500, function ($blogs) use (&$xml, $frontendUrl) {
                foreach ($blogs as $blog) {
                    $xml .= $this->urlEntry(
                        "{$frontendUrl}/blogs/{$blog->slug}",
                        $blog->updated_at->toDateString(),
                        'monthly',
                        '0.6',
                    );
                }
            });

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    private function urlEntry(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return "<url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>{$changefreq}</changefreq><priority>{$priority}</priority></url>";
    }
}
