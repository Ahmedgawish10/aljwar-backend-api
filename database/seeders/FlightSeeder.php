<?php

namespace Database\Seeders;

use App\Models\Flight;
use Illuminate\Database\Seeder;

class FlightSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('data/flights.json');

        if (! file_exists($path)) {
            $this->command?->error("Missing seed file: {$path}");
            return;
        }

        $json = json_decode(file_get_contents($path), true);

        foreach ($json['data'] ?? [] as $index => $item) {
            Flight::updateOrCreate(
                ['external_id' => $item['id']],
                array_merge($item['attributes'] ?? [], [
                    'is_active' => true,
                    'sort_order' => $index,
                ])
            );
        }
    }
}
