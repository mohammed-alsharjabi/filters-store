<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BusinessSettingsSeeder::class,
            MaterialCatalogSeeder::class,
            ServiceCatalogSeeder::class,
            ServiceAreaSeeder::class,
            LaunchArticleSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
