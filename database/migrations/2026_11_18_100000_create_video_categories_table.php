<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categories for videos, kept per owner.
 *
 * An agency's categories are its own: it groups its videos the way its
 * work is organised, which is not how another agency or the site would.
 * Both owner columns null means the category belongs to the site, for
 * the public guides page.
 *
 * The slug is unique per owner rather than across the table, so two
 * agencies may both have a "Walkthroughs" without one of them being
 * refused.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->cascadeOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained('agencies')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('slug', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['agent_id', 'agency_id', 'slug']);
            $table->index(['agent_id', 'sort_order']);
            $table->index(['agency_id', 'sort_order']);
        });

        Schema::table('videos', function (Blueprint $table) {
            /* Null on an uncategorised video, and on every video that
               predates this; a category removed leaves its videos rather
               than taking them with it. */
            $table->foreignId('video_category_id')->nullable()->after('agency_id')
                ->constrained('video_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('video_category_id');
        });

        Schema::dropIfExists('video_categories');
    }
};
