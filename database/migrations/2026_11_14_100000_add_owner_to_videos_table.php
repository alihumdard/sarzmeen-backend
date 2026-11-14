<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Videos belonging to an agent or an agency.
 *
 * Every video was the site's own: one library, all of it editorial. An
 * agent or agency posting a walkthrough of their own listing belongs on
 * their profile and nowhere else — it is theirs, not the site's, and the
 * library is a curated page rather than a feed.
 *
 * Both columns are nullable, so a video with neither is the site's own and
 * keeps behaving exactly as it did.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->after('id')
                ->constrained('agents')->nullOnDelete();
            $table->foreignId('agency_id')->nullable()->after('agent_id')
                ->constrained('agencies')->nullOnDelete();

            /*
             * The library asks for the site's own videos on every request,
             * so it reads "both null" more than anything else here.
             */
            $table->index(['agent_id', 'status']);
            $table->index(['agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['agent_id', 'status']);
            $table->dropIndex(['agency_id', 'status']);
            $table->dropConstrainedForeignId('agent_id');
            $table->dropConstrainedForeignId('agency_id');
        });
    }
};
