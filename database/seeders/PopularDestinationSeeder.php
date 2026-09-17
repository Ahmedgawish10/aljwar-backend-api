<?php

namespace Database\Seeders;

use App\Models\PopularDestination;
use Illuminate\Database\Seeder;

class PopularDestinationSeeder extends Seeder
{
    public function run(): void
    {
        $path = collect([
            base_path('data/popular-destinations/data.json'),
            base_path('data/destinations.json'),
            storage_path('app/destinations.json'),
        ])->first(fn (string $file) => file_exists($file));

        if (! $path) {
            $this->command?->error('Missing seed file: data/popular-destinations/data.json');
            return;
        }

        $json = json_decode(file_get_contents($path), true);

        foreach ($json['data'] ?? [] as $index => $item) {
            PopularDestination::updateOrCreate(
                ['external_id' => $item['id']],
                array_merge($item['attributes'] ?? [], [
                    'is_active' => true,
                    'sort_order' => $index,
                ])
            );
        }
    }
}
