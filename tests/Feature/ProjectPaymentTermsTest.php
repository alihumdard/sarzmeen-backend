<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\Admin\ProjectRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * The 100% check on a project's payment plan.
 *
 * Driven through the request's own validator rather than the HTTP endpoint,
 * so the sum is tested on its own without a project, a category, a location
 * and an admin standing in the way.
 */
class ProjectPaymentTermsTest extends TestCase
{
    /** Runs the request's after-validation hook over one set of terms. */
    private function errorsFor(array $terms): array
    {
        $request = ProjectRequest::create('/', 'POST', ['paymentTerms' => $terms]);

        $validator = Validator::make($request->all(), []);
        $request->setValidator($validator);
        $request->withValidator($validator);
        $validator->passes();

        return $validator->errors()->get('paymentTerms');
    }

    public function test_a_seven_way_split_adding_to_100_is_accepted(): void
    {
        /*
         * The case that failed: every component carries a share, and the
         * bi-annual one was left out of the total, so a balanced plan was
         * refused as totalling 85.71%.
         */
        $this->assertSame([], $this->errorsFor([
            'pctBooking' => 14.29,
            'pctConfirmation' => 14.29,
            'pctMonthly' => 14.29,
            'pctBiAnnual' => 14.29,
            'pctBalloon' => 14.29,
            'pctGrey' => 14.29,
            'pctPossession' => 14.26,
        ]));
    }

    public function test_the_bi_annual_share_counts_towards_the_total(): void
    {
        // 100% once the bi-annual share is counted, 70% without it.
        $this->assertSame([], $this->errorsFor([
            'pctBooking' => 10,
            'pctConfirmation' => 10,
            'pctMonthly' => 30,
            'pctBiAnnual' => 30,
            'pctPossession' => 20,
        ]));
    }

    public function test_a_plan_that_does_not_reach_100_is_refused(): void
    {
        $errors = $this->errorsFor([
            'pctBooking' => 10,
            'pctPossession' => 20,
        ]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('30%', $errors[0]);
    }

    public function test_a_plan_over_100_is_refused(): void
    {
        $errors = $this->errorsFor([
            'pctBooking' => 60,
            'pctBiAnnual' => 60,
        ]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('120%', $errors[0]);
    }

    public function test_an_all_zero_set_means_no_plan_and_is_allowed(): void
    {
        $this->assertSame([], $this->errorsFor([
            'pctBooking' => 0,
            'pctConfirmation' => 0,
            'pctMonthly' => 0,
            'pctBiAnnual' => 0,
            'pctBalloon' => 0,
            'pctGrey' => 0,
            'pctPossession' => 0,
        ]));
    }

    public function test_float_drift_within_a_hundredth_is_tolerated(): void
    {
        /*
         * 33.33 x 2 + 33.34 adds up to 100 in decimal but lands a hair off
         * in binary, which the tolerance is there to absorb.
         */
        $this->assertSame([], $this->errorsFor([
            'pctBooking' => 33.33,
            'pctMonthly' => 33.33,
            'pctPossession' => 33.34,
        ]));
    }
}
