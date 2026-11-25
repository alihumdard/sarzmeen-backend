<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A photo of whoever sent the map.
     *
     * Optional: it helps an administrator put a face to the name when
     * they ring back, but nobody should abandon the form over it, and a
     * photograph proves nothing about the map itself.
     */
    public function up(): void
    {
        Schema::table('society_map_submissions', function (Blueprint $table) {
            $table->string('contact_photo')->nullable()->after('contact_role');
        });
    }

    public function down(): void
    {
        Schema::table('society_map_submissions', function (Blueprint $table) {
            $table->dropColumn('contact_photo');
        });
    }
};
