<?php

namespace Database\Seeders;

use App\Models\HolidayPackage;
use App\Models\HolidayPackageBooking;
use Illuminate\Database\Seeder;

class HolidayPackageBookingSeeder extends Seeder
{
    public function run(): void
    {
        $package = HolidayPackage::query()->where('is_active', true)->orderBy('sort_order')->first();

        if (! $package) {
            $this->command?->warn('No holiday packages found. Skipping holiday package bookings.');
            return;
        }

        $travelers = 2;
        $priceFrom = (int) $package->price_from;

        HolidayPackageBooking::updateOrCreate(
            ['email' => 'nada.ibrahim@example.com', 'slug' => $package->slug],
            [
                'holiday_package_id' => $package->id,
                'full_name' => 'Nada Ibrahim',
                'phone' => '+201556667788',
                'travelers' => (string) $travelers,
                'departure' => now()->addDays(30)->toDateString(),
                'return_date' => now()->addDays(30 + max(1, (int) $package->days))->toDateString(),
                'price_from' => $priceFrom,
                'total_price' => $priceFrom * $travelers,
                'currency' => $package->currency ?? 'USD',
                'status' => 'pending',
            ]
        );
    }
}
