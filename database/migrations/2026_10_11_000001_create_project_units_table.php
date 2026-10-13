<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('unit_type');
            $table->string('category')->default('residential');
            $table->decimal('area', 12, 2);
            $table->decimal('rate_per_sqft', 12, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('project_id');
            // Filtering projects by the unit types they offer ("show me 2 Bed").
            $table->index(['project_id', 'category']);
        });

        Schema::table('projects', function (Blueprint $table) {
            // Percentages apply to the whole project: every unit is split the
            // same way, only area x rate differs between them.
            $table->jsonb('payment_terms')->default('{}');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('payment_terms');
        });

        Schema::dropIfExists('project_units');
    }
};
