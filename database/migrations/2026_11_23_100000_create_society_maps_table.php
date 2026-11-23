<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A society's map, and what is written about it.
     *
     * Kept beside `locations` rather than inside it: a location is a
     * taxonomy row that thousands of properties point at, while a map is
     * a page with an image, a description and an editor's attention. Most
     * of the 1,484 societies will never have one, and widening that table
     * for columns only a handful of rows would fill would weigh on every
     * property query that joins it.
     */
    public function up(): void
    {
        Schema::create('society_maps', function (Blueprint $table) {
            $table->id();

            /* The society (or area) this map is of. One map each: a
               second would leave no way to say which the page shows. */
            $table->foreignId('location_id')
                ->unique()
                ->constrained('locations')
                ->cascadeOnDelete();

            /* The city, resolved once when the map is saved.
               Societies sit under an area which sits under a city, so
               finding it means walking the tree — too much to do while
               filtering a listing page by city. */
            $table->foreignId('city_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();

            /* What is shown on the page: a PNG or JPG big enough to zoom
               into, since reading a plot number is the whole point. */
            $table->string('image');

            /* An optional PDF for the download button. Without one the
               button hands over the image itself. */
            $table->string('document')->nullable();

            $table->text('summary')->default('');

            /* The write-up under the map. HTML from the editor, so it is
               sanitised on the way in. */
            $table->longText('description')->default('');

            $table->string('status', 20)->default('draft');
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('downloads')->default(0);

            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['city_id', 'status']);
            $table->index(['status', 'published_at']);
            $table->index('sort_order');
        });

        /*
         * Which blogs belong under which map.
         *
         * Stated, not guessed: matching a blog to a society by name would
         * file "Bahria Town Karachi price trends" under Bahria Town Lahore,
         * and an editor would have no way to correct it.
         */
        Schema::create('blog_society_map', function (Blueprint $table) {
            $table->foreignId('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('society_map_id')->constrained()->cascadeOnDelete();
            $table->primary(['blog_id', 'society_map_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_society_map');
        Schema::dropIfExists('society_maps');
    }
};
