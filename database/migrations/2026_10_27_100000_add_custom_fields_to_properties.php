<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields a lister defines themselves.
 *
 * `specs` is driven by config/property_schema.php and drops any key that
 * schema does not declare, and `features` holds names with no value. A
 * detail the schema never anticipated — "Ceiling Height: 12 ft" — fits
 * neither, so it lives here as label, kind and value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->jsonb('custom_fields')->nullable()->after('specs');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });
    }
};
