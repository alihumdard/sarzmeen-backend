<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            /* One-line result, printed above the title on the card. */
            $table->string('outcome');
            $table->text('summary');
            /* What the client receives, as a list of lines. */
            $table->json('points');
            /* Icon key, resolved against the front-end icon library. */
            $table->string('icon', 40)->default('handshake');
            $table->string('status', 20)->default('published');
            /* The few led with, which take a wider card at the top. */
            $table->boolean('featured')->default(false);
            /* Hand-set order, so the client can arrange the grid. */
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
