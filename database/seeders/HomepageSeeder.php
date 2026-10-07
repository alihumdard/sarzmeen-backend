<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHeroBanner();
        $this->seedWhyChoose();
        $this->seedSections();
    }

    private function seedHeroBanner(): void
    {
        SiteSetting::setValue('homepage', 'hero', [
            'tagline' => "Pakistan's Trusted Property Marketplace",
            'heading' => "Find Your Perfect\nProperty in",
            'headingHighlight' => 'Pakistan',
            'description' => 'From skyline penthouses to family homes — explore verified properties for sale in all major cities of Pakistan.',
            'backgroundImage' => '/images/hero-home.png',
            'trustBadgeTitle' => 'Trusted by Thousands',
            'trustBadgeSubtitle' => '100% Verified Listings',
        ]);
    }

    private function seedWhyChoose(): void
    {
        SiteSetting::setValue('homepage', 'whyChoose', [
            'heading' => 'Why Choose Sarzameen?',
            'subtitle' => 'We make property search simple, transparent and reliable.',
            'image' => '/images/interior-1.jpg',
            'features' => [
                [
                    'id' => '1',
                    'title' => 'Wide Range of Listings',
                    'description' => 'Thousands of properties for every need',
                    'icon' => 'listings',
                ],
                [
                    'id' => '2',
                    'title' => 'Verified & Trusted',
                    'description' => 'Every listing is verified for your peace of mind',
                    'icon' => 'verified',
                ],
                [
                    'id' => '3',
                    'title' => 'Smart Search Filters',
                    'description' => 'Find properties by city, location, price & more',
                    'icon' => 'search',
                ],
                [
                    'id' => '4',
                    'title' => 'Expert Support',
                    'description' => 'Our team is here to help you at every step',
                    'icon' => 'support',
                ],
            ],
        ]);
    }

    private function seedSections(): void
    {
        SiteSetting::setValue('homepage', 'sections', [
            ['id' => 'hero', 'name' => 'Hero Banner', 'description' => 'Main banner with search', 'type' => 'Banner', 'visible' => true],
            ['id' => 'categories', 'name' => 'Browse by Category', 'description' => 'Property category cards', 'type' => 'Properties', 'visible' => true],
            ['id' => 'featured', 'name' => 'Featured Properties', 'description' => 'Showcase latest properties', 'type' => 'Properties', 'visible' => true],
            ['id' => 'testimonials', 'name' => 'Testimonials', 'description' => 'Client reviews and feedback', 'type' => 'Testimonials', 'visible' => true],
            ['id' => 'agencies', 'name' => 'Partner Agencies', 'description' => 'Verified partner agencies', 'type' => 'Content', 'visible' => true],
            ['id' => 'projects', 'name' => 'Popular Projects', 'description' => 'Featured real estate projects', 'type' => 'Projects', 'visible' => true],
            ['id' => 'whyChoose', 'name' => 'Why Choose & Cities', 'description' => 'Platform highlights and city search', 'type' => 'Content', 'visible' => true],
            ['id' => 'blogs', 'name' => 'Latest Blogs', 'description' => 'Recent articles and news', 'type' => 'Blogs', 'visible' => true],
            ['id' => 'cta', 'name' => 'CTA Strip', 'description' => 'Call to action banner', 'type' => 'CTA', 'visible' => true],
        ]);
    }
}
