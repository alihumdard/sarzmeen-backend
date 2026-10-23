<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->default('');

            /*
             * The YouTube id is kept apart from the link the admin pasted:
             * the embed and the thumbnail are both built from the id, and a
             * watch URL, a share URL and an embed URL all carry the same one.
             */
            $table->string('youtube_url');
            $table->string('youtube_id', 32);

            // Blank falls back to YouTube's own thumbnail for that id.
            $table->string('thumbnail')->nullable();

            $table->string('status', 20)->default('draft');
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();

            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['status', 'featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
