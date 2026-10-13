<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fills out the inventory fields from the specification's "Unit Type Fields".
 *
 * `area` and `rate_per_sqft` stay as they are: the payment calculator derives
 * every amount from them, so the new min/max range sits alongside rather than
 * replacing them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_units', function (Blueprint $table) {
            $table->string('unit_name')->nullable()->after('project_id');
            $table->decimal('min_area', 12, 2)->nullable()->after('area');
            $table->decimal('max_area', 12, 2)->nullable()->after('min_area');
            $table->string('area_unit', 20)->default('sqft')->after('max_area');
            $table->decimal('starting_price', 14, 2)->nullable()->after('rate_per_sqft');
            $table->decimal('max_price', 14, 2)->nullable()->after('starting_price');
            $table->string('availability', 20)->default('available')->after('max_price');

            // Residential, commercial and hospitality ask for entirely
            // different attributes, so they live in one JSON bag rather than
            // three sparse sets of columns.
            $table->jsonb('details')->default('{}')->after('availability');

            $table->index(['project_id', 'availability']);
        });

        // Seed the range from the single area already stored.
        DB::statement('UPDATE project_units SET min_area = area, max_area = area WHERE min_area IS NULL');
        DB::statement('UPDATE project_units SET starting_price = area * rate_per_sqft WHERE starting_price IS NULL');
    }

    public function down(): void
    {
        Schema::table('project_units', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'availability']);
            $table->dropColumn([
                'unit_name', 'min_area', 'max_area', 'area_unit',
                'starting_price', 'max_price', 'availability', 'details',
            ]);
        });
    }
};
