<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title', 100)->nullable();
            $table->text('bio')->nullable();
            $table->unsignedSmallInteger('years_experience')->default(0);
            $table->unsignedInteger('deals_closed')->default(0);
            $table->string('office_address')->nullable();
            $table->boolean('verified')->default(false);
            $table->string('status', 20)->default('pending');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('agency_id');
            $table->index('status');
            $table->index('verified');
        });

        Schema::create('agent_specializations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            $table->index('agent_id');
        });

        Schema::create('agent_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_languages');
        Schema::dropIfExists('agent_specializations');
        Schema::dropIfExists('agents');
    }
};
