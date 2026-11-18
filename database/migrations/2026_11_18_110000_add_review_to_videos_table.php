<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Review on an owner's video.
 *
 * An agent's or agency's video went live the moment it was added. It
 * waits for an administrator now, so there has to be somewhere to say
 * why one was turned down — "rejected" with no reason leaves the owner
 * guessing at what to change.
 *
 * The site's own guides are unaffected: an administrator adding one is
 * already the person who would approve it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->text('review_note')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('review_note');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['review_note', 'reviewed_at']);
        });
    }
};
