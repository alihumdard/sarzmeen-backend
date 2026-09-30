<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->foreignId('project_category_id')->constrained('project_categories');
            $table->string('developer');
            $table->foreignId('location_id')->constrained('locations');
            $table->string('full_location')->default('');
            $table->string('status', 30)->default('Launching Soon');
            $table->string('price_from')->default('');
            $table->string('total_area')->default('');
            $table->unsignedInteger('total_units')->nullable();
            $table->text('description')->default('');
            $table->boolean('verified')->default(false);
            $table->boolean('featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['featured', 'created_at']);
            $table->index('project_category_id');
            $table->index('location_id');
        });

        Schema::create('project_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('path');
            $table->boolean('is_cover')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->index('project_id');
        });

        Schema::create('project_amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');

            $table->index('project_id');
        });

        Schema::create('project_payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->index('project_id');
        });

        Schema::create('project_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('text');

            $table->index('project_id');
        });

        Schema::create('project_plot_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('size');

            $table->index('project_id');
        });

        // Add FK constraint on properties.project_id now that projects table exists
        Schema::table('properties', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });

        Schema::dropIfExists('project_plot_sizes');
        Schema::dropIfExists('project_highlights');
        Schema::dropIfExists('project_payment_plans');
        Schema::dropIfExists('project_amenities');
        Schema::dropIfExists('project_images');
        Schema::dropIfExists('projects');
    }
};
