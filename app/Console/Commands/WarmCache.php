<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Location;
use App\Models\ProjectCategory;
use App\Models\PropertyType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmCache extends Command
{
    protected $signature = 'cache:warm';
    protected $description = 'Pre-warm taxonomy and frequently accessed caches';

    public function handle(): int
    {
        Cache::remember('public_categories', 3600, fn () => Category::orderBy('name')->get());
        $this->info('Cached categories.');

        Cache::remember('public_property_types', 3600, fn () => PropertyType::orderBy('name')->get());
        $this->info('Cached property types.');

        Cache::remember('public_project_categories', 3600, fn () => ProjectCategory::orderBy('name')->get());
        $this->info('Cached project categories.');

        Cache::remember('public_locations', 3600, fn () => Location::with('children')->whereNull('parent_id')->orderBy('name')->get());
        $this->info('Cached locations.');

        $this->info('Cache warm-up complete.');

        return self::SUCCESS;
    }
}
