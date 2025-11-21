<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            IndustrySeeder::class,
            ApplicationSeeder::class,
            IsoPurityLevelSeeder::class,
            IsoConfigurationSeeder::class,
            ProductRangeSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
