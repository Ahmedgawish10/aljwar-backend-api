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
            RolePermissionSeeder::class,
            TransferSeeder::class,
            VisaDestinationSeeder::class,
            FlightSeeder::class,
            HotelSeeder::class,
            DailyTourCategorySeeder::class,
            DailyTourSeeder::class,
            PopularDestinationCategorySeeder::class,
            PopularDestinationSeeder::class,
            AboutUsSeeder::class,
            HolidayPackageSeeder::class,
            HolidayPackageBookingSeeder::class,
            BookNowSeeder::class,
            NewsletterSeeder::class,
        ]);
    }
}
