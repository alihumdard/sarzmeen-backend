<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->string('agency_type', 50)->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->boolean('verified')->default(false);
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('city');
            $table->index('verified');
        });

        Schema::create('agency_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            $table->index('agency_id');
        });

        Schema::create('agency_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            $table->index('agency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_services');
        Schema::dropIfExists('agency_locations');
        Schema::dropIfExists('agencies');
    }
};
