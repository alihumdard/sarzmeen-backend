<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily view counts for everything but properties.
 *
 * Only properties were ever counted, so nobody could say whether people
 * came for the projects, read the blog, or looked at an agency at all.
 *
 * One table rather than one per kind: the shape is identical each time,
 * and the dashboard reads them together. Properties keep their own table,
 * which already holds months of rows and is what the existing chart is
 * built on — moving it would risk real data for no gain.
 *
 * Nothing constrains the id to a particular table, since it points at a
 * different one per kind; a row for something since deleted simply has
 * nothing to name, which is what the dashboard expects anyway.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_view_logs', function (Blueprint $table) {
            $table->id();
            $table->string('viewable_type', 20);
            $table->unsignedBigInteger('viewable_id');
            $table->date('date');
            $table->unsignedInteger('count')->default(1);

            /* One row per thing per day, which the upsert depends on. */
            $table->unique(['viewable_type', 'viewable_id', 'date']);

            /* The dashboard groups by kind over a window of days. */
            $table->index(['viewable_type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_view_logs');
    }
};
