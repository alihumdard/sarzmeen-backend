<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BlogStatus;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use RecordsActivity;
    use SoftDeletes;

    protected array $activityFields = ['title', 'status', 'published_at', 'featured'];

    protected $fillable = [
        'title',
        'slug',
        'description',
        'youtube_url',
        'youtube_id',
        'thumbnail',
        'status',
        'featured',
        'sort_order',
        'published_at',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            // Videos publish like blog posts, so they share the status enum.
            'status' => BlogStatus::class,
            'featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', BlogStatus::Published);
    }

    /**
     * Pulls the video id out of any YouTube link an admin might paste.
     *
     * Covers watch URLs, share links, embeds and shorts — a bare id is also
     * accepted, since that is what people often copy from a share dialog.
     */
    public static function extractYoutubeId(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $patterns = [
            '#youtu\.be/([A-Za-z0-9_-]{11})#',
            '#youtube\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{11})#',
            '#youtube\.com/embed/([A-Za-z0-9_-]{11})#',
            '#youtube\.com/shorts/([A-Za-z0-9_-]{11})#',
            '#youtube\.com/live/([A-Za-z0-9_-]{11})#',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches) === 1) {
                return $matches[1];
            }
        }

        // A bare id pasted on its own.
        if (preg_match('#^[A-Za-z0-9_-]{11}$#', $url) === 1) {
            return $url;
        }

        return null;
    }

    /** YouTube's own still, used when no thumbnail was uploaded. */
    public function thumbnailUrl(): string
    {
        if ($this->thumbnail) {
            return $this->thumbnail;
        }

        return "https://img.youtube.com/vi/{$this->youtube_id}/hqdefault.jpg";
    }
}
