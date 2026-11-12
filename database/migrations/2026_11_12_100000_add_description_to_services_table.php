<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A service's own page printed the summary twice: once under the title in
 * the hero and again below the pill. One field cannot be both a line that
 * fits over a photograph and the full account of the service, so a long
 * summary filled the hero and buried the image behind it.
 *
 * The summary stays the short line. This holds the longer text, and is
 * nullable so every service written before it keeps working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->text('description')->nullable()->after('summary');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
