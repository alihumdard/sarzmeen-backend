<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            TaxonomySeeder::class,
            PropertySeeder::class,
            ProjectSeeder::class,
            ContentSeeder::class,
            HomepageSeeder::class,
            // Defaults for the admin Settings screen (general, SEO, appearance).
            SiteSettingsSeeder::class,
            // Converts legacy text payment plans into structured rows.
            BackfillPaymentPlansSeeder::class,
        ]);
    }
}
