<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Developers become records rather than a free-text name, so a project can
 * show the developer's contact details and list their other projects.
 *
 * Adds the per-project content the specification asks for: FAQs, documents
 * and floor plans.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('developers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->default('');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('projects', function (Blueprint $table) {
            // Nullable, and the original `developer` string stays: existing rows
            // keep rendering while developers are filled in.
            $table->foreignId('developer_id')->nullable()->after('developer')
                ->constrained('developers')->nullOnDelete();
        });

        Schema::create('project_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->index('project_id');
        });

        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 40)->default('other');
            $table->string('path');
            // Private documents are listed for admins but withheld from the site.
            $table->boolean('is_public')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['project_id', 'is_public']);
        });

        Schema::create('project_floor_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('floor_number')->nullable();
            $table->string('tower')->nullable();
            $table->string('image')->nullable();
            $table->string('pdf')->nullable();
            $table->text('description')->default('');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('project_id');
        });

        // Turn the names already on projects into developer records.
        $names = DB::table('projects')
            ->whereNotNull('developer')
            ->where('developer', '<>', '')
            ->distinct()
            ->pluck('developer');

        foreach ($names as $name) {
            $slug = Str::slug($name) ?: 'developer-'.Str::lower(Str::random(6));

            // Str::slug() can collide once punctuation is stripped.
            $base = $slug;
            $i = 2;
            while (DB::table('developers')->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i++;
            }

            $id = DB::table('developers')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'description' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('projects')->where('developer', $name)->update(['developer_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_floor_plans');
        Schema::dropIfExists('project_documents');
        Schema::dropIfExists('project_faqs');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('developer_id');
        });

        Schema::dropIfExists('developers');
    }
};
