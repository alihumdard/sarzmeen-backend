<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Defaults for the settings groups the admin panel edits.
 *
 * Uses the existing value when a key is already present, so re-running this
 * never overwrites what an admin has configured.
 */
class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'general' => [
                'siteName' => 'Sarzameen',
                'tagline' => 'Your Property, Our Priority',
                'supportEmail' => 'support@sarzameen.com',
                'phone' => '',
                'whatsapp' => '',
                'address' => '',
                'timezone' => 'Asia/Karachi',
                'description' => "Pakistan's trusted real estate marketplace.",
            ],

            'seo' => [
                'metaTitle' => 'Sarzameen | Property for Sale & Rent in Pakistan',
                'titleTemplate' => '%s | Sarzameen',
                'metaDescription' => 'Browse verified properties, plots and projects for sale and rent across Pakistan.',
                'metaKeywords' => 'property pakistan, plots for sale, houses for rent, real estate',
                'ogImage' => '',
                'canonicalUrl' => 'https://sarzameen.com',
                'googleAnalyticsId' => '',
                'googleSiteVerification' => '',
                'facebookPixelId' => '',
                'robotsIndex' => true,
            ],

            'appearance' => [
                'logo' => '',
                'favicon' => '',
                'primaryColor' => '#1f7a4d',
                'primaryDark' => '#155c39',
                'primaryLight' => '#e8f5ee',
                'footerText' => '© 2026 Sarzameen.com. All rights reserved.',
                'facebookUrl' => '',
                'instagramUrl' => '',
                'linkedinUrl' => '',
                'youtubeUrl' => '',
            ],

            'email' => [
                'fromName' => 'Sarzameen',
                'fromAddress' => 'no-reply@sarzameen.com',
                'replyTo' => 'support@sarzameen.com',
                'notifyOnInquiry' => true,
                'notifyOnRegistration' => true,
            ],

            'system' => [
                'maintenanceMode' => false,
                'allowRegistration' => true,
                'autoApproveListings' => false,
                'listingsPerPage' => 12,
                'maxImagesPerListing' => 20,
            ],
        ];

        foreach ($defaults as $group => $values) {
            foreach ($values as $key => $value) {
                if (SiteSetting::getValue($group, $key) === null) {
                    SiteSetting::setValue($group, $key, $value);
                }
            }
        }
    }
}
