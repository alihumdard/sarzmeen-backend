<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A link and tags on an agent's or agency's video.
 *
 * The link is whatever the video is about — the listing it walks through,
 * the project it shows — so a viewer can go from watching to the thing
 * itself. It is separate from youtube_url, which is where the video
 * plays from.
 *
 * Tags are typed rather than chosen from a list, so they are kept on the
 * row as a json array rather than in a table of their own: nothing else
 * needs to join on them, and a shared vocabulary is not what was asked
 * for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('link', 500)->nullable()->after('youtube_url');
            $table->json('tags')->nullable()->after('link');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['link', 'tags']);
        });
    }
};
