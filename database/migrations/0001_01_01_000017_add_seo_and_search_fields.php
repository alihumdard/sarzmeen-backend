<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('expires_at');
            $table->string('meta_description')->nullable()->after('meta_title');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('featured');
            $table->string('meta_description')->nullable()->after('meta_title');
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('featured');
            $table->string('meta_description')->nullable()->after('meta_title');
        });

        // Full-text search indexes (PostgreSQL)
        DB::statement("CREATE INDEX properties_fulltext_idx ON properties USING gin (to_tsvector('english', coalesce(title, '') || ' ' || coalesce(description, '') || ' ' || coalesce(full_location, '')))");
        DB::statement("CREATE INDEX projects_fulltext_idx ON projects USING gin (to_tsvector('english', coalesce(name, '') || ' ' || coalesce(description, '') || ' ' || coalesce(full_location, '')))");
        DB::statement("CREATE INDEX blogs_fulltext_idx ON blogs USING gin (to_tsvector('english', coalesce(title, '') || ' ' || coalesce(excerpt, '') || ' ' || coalesce(content, '')))");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS properties_fulltext_idx');
        DB::statement('DROP INDEX IF EXISTS projects_fulltext_idx');
        DB::statement('DROP INDEX IF EXISTS blogs_fulltext_idx');

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description']);
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description']);
        });
    }
};
