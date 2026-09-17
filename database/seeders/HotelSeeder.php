<?php

namespace Database\Seeders;

use App\Models\Hotel;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('data/hotels.json');

        if (! file_exists($path)) {
            $this->command?->error("Missing seed file: {$path}");
            return;
        }

        $json = json_decode(file_get_contents($path), true);

        foreach ($json['data'] ?? [] as $index => $item) {
            $hotel = $item['attributes'] ?? $item;

            if (is_array($hotel['description'] ?? null)) {
                $hotel['description'] = implode("\n\n", $hotel['description']);
            }

            $hotel['main_image'] = $hotel['main_image'] ?? $hotel['image'] ?? null;
            unset($hotel['image']);

            Hotel::updateOrCreate(
                ['external_id' => $item['id'] ?? $hotel['slug']],
                array_merge($hotel, [
                    'is_active' => true,
                    'sort_order' => $index,
                ])
            );
        }
    }
}
