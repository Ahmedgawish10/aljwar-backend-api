<?php

namespace Database\Seeders;

use App\Models\VisaDestination;
use Illuminate\Database\Seeder;

class VisaDestinationSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'country_name' => 'Turkey',
                'country_image' => 'visas/turkey-dest.jpg',
                'country_flag' => 'visas/turkey-flag.jpg',
                'price' => 60,
            ],
            [
                'country_name' => 'UAE',
                'country_image' => 'visas/uae.jpg',
                'country_flag' => 'visas/uae-flag.jpg',
                'price' => 90,
            ],
            [
                'country_name' => 'Saudi Arabia',
                'country_image' => 'visas/saudi.jpg',
                'country_flag' => 'visas/saudi-flag.jpg',
                'price' => 80,
            ],
            [
                'country_name' => 'USA',
                'country_image' => 'visas/usa.jpg',
                'country_flag' => 'visas/usa-flag.jpg',
                'price' => 160,
            ],
            [
                'country_name' => 'UK',
                'country_image' => 'visas/uk.jpg',
                'country_flag' => 'visas/uk-flag.jpg',
                'price' => 150,
            ],
            [
                'country_name' => 'Schengen',
                'country_image' => 'visas/schengen.jpg',
                'country_flag' => 'visas/schengen-flag.avif',
                'price' => 110,
            ],
        ];

        foreach ($items as $index => $item) {
            VisaDestination::updateOrCreate(
                ['country_name' => $item['country_name']],
                array_merge($item, [
                    'is_active' => true,
                    'sort_order' => $index,
                ])
            );
        }
    }
}
