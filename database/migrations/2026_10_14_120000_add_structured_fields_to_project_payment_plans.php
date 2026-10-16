<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment plans were label/value text ("Down Payment" => "25%"), which cannot
 * be calculated with. These columns hold the same plan as numbers so a project
 * can generate a real schedule.
 *
 * The original label/value columns stay: existing rows keep rendering, and a
 * row is only treated as structured once `component` is set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_payment_plans', function (Blueprint $table) {
            // Which part of the plan this row is: booking, monthly, balloon...
            $table->string('component', 40)->nullable()->after('project_id');

            // Exactly one of these drives the row; the other is derived.
            $table->decimal('percent', 8, 4)->nullable()->after('component');
            $table->decimal('amount', 14, 2)->nullable()->after('percent');

            $table->unsignedSmallInteger('count')->default(1)->after('amount');
            $table->string('frequency', 20)->default('once')->after('count');

            // 'percent' | 'amount' | 'none' — 'none' absorbs the remainder.
            $table->string('pinned', 10)->default('none')->after('frequency');

            // Groups rows into named plans, so one project can offer several.
            $table->string('plan_name')->nullable()->after('pinned');

            $table->index(['project_id', 'plan_name']);
        });
    }

    public function down(): void
    {
        Schema::table('project_payment_plans', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'plan_name']);
            $table->dropColumn([
                'component', 'percent', 'amount', 'count',
                'frequency', 'pinned', 'plan_name',
            ]);
        });
    }
};
