<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A map sent in by a society owner or developer.
     *
     * Kept apart from `society_maps` rather than written straight into it
     * as a draft: a submission carries things a published map has no use
     * for — who sent it, how to reach them, what they claim about the
     * NOC — and most submissions will never become a map at all. Mixing
     * the two would leave every published row carrying a sender's phone
     * number, and every listing query stepping over rejected ones.
     */
    public function up(): void
    {
        Schema::create('society_map_submissions', function (Blueprint $table) {
            $table->id();

            /* The society as the sender names it. They may be offering a
               scheme the site has never heard of, so this is free text;
               an administrator ties it to a location when publishing. */
            $table->string('society_name');
            $table->string('city_name')->nullable();

            /* Set only once an administrator matches it to a real one. */
            $table->foreignId('location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            /* What the sender claims about approval. Their word, not the
               site's: shown to the administrator, never published as
               fact. Null means they did not say. */
            $table->boolean('noc_approved')->nullable();
            $table->string('noc_authority')->nullable();

            /* Who sent it. */
            $table->string('contact_name');
            $table->string('contact_phone', 32);
            $table->string('contact_email')->nullable();
            $table->string('contact_role', 64)->nullable();

            /* Signed in when they submitted, if they were. */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->text('notes')->default('');

            /* The map itself, and an optional document. */
            $table->string('image');
            $table->string('document')->nullable();

            $table->string('status', 20)->default('pending');

            /* Why it was turned down, shown back to the sender. */
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            /* The map this became, once published. */
            $table->foreignId('society_map_id')
                ->nullable()
                ->constrained('society_maps')
                ->nullOnDelete();

            $table->ipAddress('submitted_ip')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('society_map_submissions');
    }
};
