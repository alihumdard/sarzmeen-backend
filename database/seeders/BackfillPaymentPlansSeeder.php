<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProjectPaymentPlan;
use Illuminate\Database\Seeder;

/**
 * Converts the old free-text plan rows ("Down Payment" => "25%") into the
 * structured columns, so existing projects can calculate without being
 * re-entered by hand.
 *
 * Rows whose value cannot be read as a number keep their text and are left
 * unstructured rather than guessed at.
 */
class BackfillPaymentPlansSeeder extends Seeder
{
    /** Maps a free-text label onto a component key. */
    private const LABEL_MAP = [
        'booking' => 'booking',
        'confirmation' => 'confirmation',
        'down payment' => 'booking',
        'downpayment' => 'booking',
        'monthly' => 'monthly',
        'monthly installment' => 'monthly',
        'monthly installments' => 'monthly',
        'quarterly' => 'quarterly',
        'bi-annual' => 'biannual',
        'biannual' => 'biannual',
        'half yearly' => 'biannual',
        'annual' => 'annual',
        'yearly' => 'annual',
        'balloon' => 'balloon',
        'grey structure' => 'grey',
        'grey' => 'grey',
        'possession' => 'possession',
        'on possession' => 'possession',
    ];

    private const FREQUENCY = [
        'monthly' => 'monthly',
        'quarterly' => 'quarterly',
        'biannual' => 'biannual',
        'annual' => 'annual',
        'balloon' => 'balloon',
    ];

    public function run(): void
    {
        $structured = 0;
        $skipped = 0;

        foreach (ProjectPaymentPlan::whereNull('component')->get() as $row) {
            $label = strtolower(trim((string) $row->label));
            $component = self::LABEL_MAP[$label] ?? null;

            if (! $component) {
                $skipped++;
                continue;
            }

            $value = trim((string) $row->value);

            // "25%" -> a percentage; "36 months" -> a count; anything else is text.
            if (preg_match('/^([\d.]+)\s*%$/', $value, $m)) {
                $row->component = $component;
                $row->percent = (float) $m[1];
                $row->pinned = 'percent';
            } elseif (preg_match('/^(\d+)\s*(months?|payments?|installments?)$/i', $value, $m)) {
                $row->component = $component;
                $row->count = (int) $m[1];
                // A count alone does not fix the share, so let it absorb the rest.
                $row->pinned = 'none';
            } else {
                $skipped++;
                continue;
            }

            $row->frequency = self::FREQUENCY[$component] ?? 'once';
            $row->save();
            $structured++;
        }

        $this->command?->info("Structured {$structured} plan rows, left {$skipped} as text.");
    }
}
