<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('reference', 20)->unique();
            $table->string('title');
            $table->string('headline')->nullable();
            $table->text('description')->default('');

            $table->morphs('owner');
            $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete();

            $table->foreignId('property_type_id')->constrained('property_types');
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->foreignId('location_id')->constrained('locations');
            $table->string('full_location')->default('');

            $table->string('purpose', 10);
            $table->decimal('price', 15, 2);
            $table->boolean('negotiable')->default(false);
            $table->decimal('area_value', 10, 2);
            $table->string('area_unit', 10);
            $table->unsignedSmallInteger('beds')->nullable();
            $table->unsignedSmallInteger('baths')->nullable();
            $table->unsignedSmallInteger('living_rooms')->nullable();
            $table->unsignedSmallInteger('kitchens')->nullable();
            $table->unsignedSmallInteger('car_parking')->nullable();
            $table->unsignedSmallInteger('floors')->nullable();

            $table->string('furnishing', 20)->nullable();
            $table->string('property_condition', 30)->nullable();
            $table->string('listed_by', 10);

            $table->string('status', 20)->default('draft');
            $table->boolean('featured')->default(false);
            $table->boolean('verified')->default(false);
            $table->unsignedInteger('views')->default(0);

            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'purpose']);
            $table->index(['status', 'featured']);
            $table->index(['status', 'published_at']);
            $table->index('location_id');
            $table->index('property_type_id');
            $table->index('agency_id');
            $table->index('price');
        });

        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('path');
            $table->boolean('is_cover')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('property_id');
        });

        Schema::create('property_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('name');

            $table->index('property_id');
        });

        Schema::create('property_nearby_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('name');
            $table->string('distance', 50);
            $table->string('kind', 20);

            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_nearby_places');
        Schema::dropIfExists('property_features');
        Schema::dropIfExists('property_images');
        Schema::dropIfExists('properties');
    }
};
