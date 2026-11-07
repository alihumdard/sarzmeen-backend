<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * The six services the page carried as hard-coded copy, moved into the table
 * so the wording survives the switch and the client edits it from the admin.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'slug' => 'property-dealing',
                'title' => 'Property Dealing',
                'outcome' => 'From first viewing to transfer',
                'summary' => 'Buying, selling or renting — we handle the search, the viewings and the paperwork that follows.',
                'points' => [
                    'Shortlists built around your budget and the areas you are considering',
                    'Viewings arranged and attended with you',
                    'Registry and ownership checks before any token is paid',
                    'Negotiation and transfer handled end to end',
                ],
                'icon' => 'handshake',
                'featured' => true,
                'position' => 1,
            ],
            [
                'slug' => 'payment-plans',
                'title' => 'Payment Plan Creation',
                'outcome' => 'A schedule your buyers can read',
                'summary' => 'Structured instalment plans for developers, worked out to the rupee and ready to publish.',
                'points' => [
                    'Booking, confirmation, instalments, balloons and possession split across any duration',
                    'Plans checked both ways — set a percentage or a monthly figure and the rest follows',
                    'A schedule your buyers can read, download and compare',
                    'Published straight to your project page on Sarzameen.com',
                ],
                'icon' => 'document',
                'featured' => true,
                'position' => 2,
            ],
            [
                'slug' => 'seo',
                'title' => 'SEO & Digital Marketing',
                'outcome' => 'Found by people already searching',
                'summary' => 'Getting your project found by people already searching for it.',
                'points' => [
                    'Keyword research for the areas and property types you sell in',
                    'Page titles, descriptions and content written for search',
                    'Technical fixes: speed, mobile, indexing and structured data',
                    'Monthly reporting on rankings and enquiries, not vanity numbers',
                ],
                'icon' => 'search',
                'featured' => false,
                'position' => 3,
            ],
            [
                'slug' => 'websites',
                'title' => 'Property Website Development',
                'outcome' => 'A site your team can run',
                'summary' => 'Listing websites for agencies and developers, built the way this one is.',
                'points' => [
                    'Property and project listings with search, filters and enquiry forms',
                    'An admin panel your team can run without a developer',
                    'Payment plan calculators and downloadable schedules',
                    'Fast on a phone, which is where most of your buyers are',
                ],
                'icon' => 'globe',
                'featured' => false,
                'position' => 4,
            ],
            [
                'slug' => 'photography',
                'title' => 'Photography & Virtual Tours',
                'outcome' => 'Listings people actually open',
                'summary' => 'Listings with good photographs get opened. The rest get scrolled past.',
                'points' => [
                    'Professional interior and exterior photography',
                    'Drone footage for plots, societies and high-rise projects',
                    'Video walkthroughs for the listing and for social media',
                    'Images sized and optimised for the web, ready to upload',
                ],
                'icon' => 'camera',
                'featured' => false,
                'position' => 5,
            ],
            [
                'slug' => 'advisory',
                'title' => 'Investment Advisory',
                'outcome' => 'A straight answer before you commit',
                'summary' => 'An honest read on what an area is doing before you commit to it.',
                'points' => [
                    'Area-by-area price trends and rental yields',
                    'Developer track record and possession risk',
                    'Comparison of projects on total cost, not headline price',
                    'A written recommendation you can act on or ignore',
                ],
                'icon' => 'chart',
                'featured' => false,
                'position' => 6,
            ],
        ];

        foreach ($services as $service) {
            /*
             * Keyed on the slug, so re-running leaves anything the client has
             * since reworded alone rather than overwriting it.
             */
            Service::firstOrCreate(
                ['slug' => $service['slug']],
                $service + ['status' => 'published'],
            );
        }
    }
}
