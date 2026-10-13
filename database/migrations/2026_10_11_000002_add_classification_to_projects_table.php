<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits a project's single "category" into the separate attributes the
 * specification calls for: Type, Format, Payment Type and Status.
 *
 * One project is several things at once — "Mixed Use + High-Rise +
 * Installment + Under Construction" — which a single category cannot express.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('project_type', 30)->default('residential')->after('project_category_id');
            $table->string('project_format', 30)->default('other')->after('project_type');
            $table->string('payment_type', 30)->default('full_payment')->after('project_format');

            // Specifications (spec section 4).
            $table->unsignedSmallInteger('towers_count')->nullable()->after('total_units');
            $table->unsignedSmallInteger('total_floors')->nullable()->after('towers_count');
            $table->unsignedSmallInteger('basement_floors')->nullable()->after('total_floors');
            $table->unsignedInteger('parking_spaces')->nullable()->after('basement_floors');
            $table->string('expected_completion')->nullable()->after('parking_spaces');
            $table->string('possession_timeline')->nullable()->after('expected_completion');

            $table->index(['project_type', 'project_format']);
            $table->index('payment_type');
            $table->index('status');
        });

        // Carry the existing category across, since it already encodes either a
        // type or a format depending on the row.
        $byType = [
            'residential-project' => 'residential',
            'commercial-project' => 'commercial',
            'mixed-use-project' => 'mixed_use',
            'industrial-estate' => 'commercial',
        ];

        foreach ($byType as $slug => $type) {
            DB::table('projects')
                ->whereIn('project_category_id', fn ($q) => $q->select('id')->from('project_categories')->where('slug', $slug))
                ->update(['project_type' => $type]);
        }

        $byFormat = [
            'high-raise-project' => 'high_rise',
            'farmhouse-society' => 'housing_community',
            'overseas-housing-scheme' => 'housing_community',
        ];

        foreach ($byFormat as $slug => $format) {
            DB::table('projects')
                ->whereIn('project_category_id', fn ($q) => $q->select('id')->from('project_categories')->where('slug', $slug))
                ->update(['project_format' => $format]);
        }

        // Statuses were free text, so existing rows hold values the new fixed
        // list does not contain.
        $statusMap = [
            'Launching Soon' => 'Pre-Launch',
            'Active' => 'Booking Open',
            'Upcoming' => 'Pre-Launch',
            'On Hold' => 'Pre-Launch',
            'Completed' => 'Completed',
            'Under Construction' => 'Under Construction',
            'Ready to Move' => 'Ready to Move',
        ];

        foreach ($statusMap as $from => $to) {
            DB::table('projects')->where('status', $from)->update(['status' => $to]);
        }

        // Projects that already carry an instalment plan are, by definition,
        // sold on instalments.
        DB::table('projects')
            ->whereRaw("payment_terms::text <> '{}'")
            ->update(['payment_type' => 'installment']);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['project_type', 'project_format']);
            $table->dropIndex(['payment_type']);
            $table->dropIndex(['status']);

            $table->dropColumn([
                'project_type', 'project_format', 'payment_type',
                'towers_count', 'total_floors', 'basement_floors',
                'parking_spaces', 'expected_completion', 'possession_timeline',
            ]);
        });
    }
};
