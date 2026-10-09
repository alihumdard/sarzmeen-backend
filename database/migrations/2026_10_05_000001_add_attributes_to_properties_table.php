<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Type-specific fields defined in config/property_schema.php.
            // Global, filterable fields stay as real columns above.
            // Named `specs` rather than `attributes`: Eloquent already uses
            // $attributes internally for a model's raw column values.
            $table->jsonb('specs')->default('{}');
        });

        // GIN index makes containment queries (specs @> '{"cornerPlot":true}')
        // usable for filtering without promoting each field to a column.
        Schema::getConnection()->statement(
            'CREATE INDEX properties_specs_gin ON properties USING gin (specs)'
        );
    }

    public function down(): void
    {
        Schema::getConnection()->statement('DROP INDEX IF EXISTS properties_specs_gin');

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('specs');
        });
    }
};
