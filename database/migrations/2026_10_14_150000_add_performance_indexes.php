<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Indexes for the sorts and filters the listing endpoints actually use.
 *
 * Raw SQL with IF NOT EXISTS so the migration is safe to re-run and never
 * collides with an index added elsewhere. CONCURRENTLY is deliberately not
 * used — it cannot run inside a transaction, and these tables are small.
 */
return new class extends Migration
{
    /** @var list<array{0:string,1:string}> name => definition */
    private array $indexes = [
        // `latest()` sorts on created_at; properties had no index for it.
        ['properties_created_at_index', 'properties (created_at DESC)'],
        ['projects_created_at_index', 'projects (created_at DESC)'],

        // Owner/agency dashboards and the agent profile counts.
        ['properties_owner_status_purpose_index', 'properties (owner_id, status, purpose)'],
        ['properties_agency_status_index', 'properties (agency_id, status)'],

        // Bedroom and area filters on the search page.
        ['properties_status_beds_index', 'properties (status, beds)'],
        ['properties_status_area_value_index', 'properties (status, area_value)'],

        // Foreign keys that had no index of their own.
        ['blogs_blog_category_id_index', 'blogs (blog_category_id)'],
        ['property_types_category_id_index', 'property_types (category_id)'],

        // Tag filtering reads the pivot the other way round from its PK.
        ['blog_blog_tag_tag_id_index', 'blog_blog_tag (blog_tag_id, blog_id)'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as [$name, $definition]) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$name} ON {$definition}");
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as [$name]) {
            DB::statement("DROP INDEX IF EXISTS {$name}");
        }
    }
};
