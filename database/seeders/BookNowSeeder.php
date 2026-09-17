<?php

namespace Database\Seeders;

use App\Models\BookNowFlight;
use App\Models\BookNowHotel;
use App\Models\BookNowTour;
use App\Models\BookNowTransfer;
use App\Models\BookNowVisa;
use Illuminate\Database\Seeder;

class BookNowSeeder extends Seeder
{
    public function run(): void
    {
        BookNowFlight::updateOrCreate(
            ['email' => 'ahmed.nabil@example.com', 'from' => 'Cairo', 'to' => 'Dubai'],
            [
                'full_name' => 'Ahmed Nabil',
                'phone' => '+201001112233',
                'trip_type' => 'roundTrip',
                'departure_date' => now()->addDays(14)->toDateString(),
                'return_date' => now()->addDays(21)->toDateString(),
                'passengers' => '2',
                'class' => 'economy',
                'airline' => 'emirates',
                'special_requests' => 'Window seats if available',
                'status' => 'pending',
            ]
        );

        BookNowHotel::updateOrCreate(
            ['email' => 'mona.fathy@example.com', 'destination' => 'Sharm El Sheikh'],
            [
                'full_name' => 'Mona Fathy',
                'phone' => '+201112223344',
                'check_in' => now()->addDays(10)->toDateString(),
                'check_out' => now()->addDays(14)->toDateString(),
                'guests' => '2',
                'rooms' => '1',
                'special_requests' => 'Sea view room',
                'status' => 'pending',
            ]
        );

        BookNowTour::updateOrCreate(
            ['email' => 'karim.saleh@example.com', 'destination' => 'Luxor'],
            [
                'full_name' => 'Karim Saleh',
                'phone' => '+201223334455',
                'travel_date' => now()->addDays(20)->toDateString(),
                'passengers' => '3',
                'special_requests' => null,
                'status' => 'pending',
            ]
        );

        BookNowTransfer::updateOrCreate(
            ['email' => 'layla.hassan@example.com', 'from' => 'Cairo Airport', 'to' => 'Downtown Cairo'],
            [
                'full_name' => 'Layla Hassan',
                'phone' => '+201334445566',
                'travel_date' => now()->addDays(7)->toDateString(),
                'passengers' => '1',
                'special_requests' => 'Meet and greet at arrivals',
                'status' => 'pending',
            ]
        );

        BookNowVisa::updateOrCreate(
            ['email' => 'youssef.adel@example.com', 'visa_country' => 'schengen'],
            [
                'full_name' => 'Youssef Adel',
                'phone' => '+201445556677',
                'nationality' => 'egypt',
                'travel_date' => now()->addDays(45)->toDateString(),
                'special_requests' => null,
                'status' => 'pending',
            ]
        );
    }
}
