<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // problem | suggestion | change_request | other
            $table->string('type', 32);
            $table->string('subject');
            $table->text('body');
            $table->string('attachment')->nullable();
            // new | in_review | resolved | declined
            $table->string('status', 16)->default('new');
            $table->text('admin_reply')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            // The admin list reads newest-first, filtered by status.
            $table->index(['status', 'created_at']);
            // The sender's own list.
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_reports');
    }
};
