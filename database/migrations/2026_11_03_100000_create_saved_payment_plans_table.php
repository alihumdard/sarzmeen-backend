<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');

            /*
             * The calculator's whole input state, as JSON rather than one
             * column per field: the components it splits a price across are
             * the frontend's own list and keep changing, and a column each
             * would mean a migration every time one is added. Nothing here
             * is queried -- a plan is only ever loaded whole.
             */
            $table->json('inputs');

            /*
             * Totals are real columns because the list shows them without
             * opening a plan, and digging them out of the JSON for every
             * row would mean recomputing the whole split to display a page.
             */
            $table->decimal('total_price', 15, 2)->default(0);
            $table->decimal('net_total', 15, 2)->default(0);

            $table->timestamps();

            // Every read is "this user's plans, newest first".
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_payment_plans');
    }
};
