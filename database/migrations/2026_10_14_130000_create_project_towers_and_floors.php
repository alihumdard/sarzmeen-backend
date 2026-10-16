<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Project -> Tower -> Floor -> Unit, per the projects spec.
 *
 * Purely additive: `project_units` keeps working exactly as it does today and
 * only gains optional tower/floor links, so a project can list unit types
 * without modelling towers at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_towers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('floors_count')->nullable();
            $table->unsignedSmallInteger('units_count')->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });

        Schema::create('project_floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_tower_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            // Signed: basements are negative, ground is 0.
            $table->smallInteger('floor_number');
            $table->string('floor_plan_image')->nullable();
            $table->string('floor_plan_pdf')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_tower_id', 'floor_number']);
        });

        Schema::table('project_units', function (Blueprint $table) {
            // Optional: a unit type can exist without being tied to a tower.
            $table->foreignId('project_tower_id')->nullable()->after('project_id')
                ->constrained()->nullOnDelete();
            $table->foreignId('project_floor_id')->nullable()->after('project_tower_id')
                ->constrained()->nullOnDelete();
            $table->string('unit_number')->nullable()->after('unit_name');
        });
    }

    public function down(): void
    {
        Schema::table('project_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_tower_id');
            $table->dropConstrainedForeignId('project_floor_id');
            $table->dropColumn('unit_number');
        });

        Schema::dropIfExists('project_floors');
        Schema::dropIfExists('project_towers');
    }
};
