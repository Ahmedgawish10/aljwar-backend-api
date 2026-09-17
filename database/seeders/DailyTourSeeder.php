<?php

namespace Database\Seeders;

use App\Models\DailyTour;
use App\Models\DailyTourCategory;
use Illuminate\Database\Seeder;

class DailyTourSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('data/daily-tours/data.json');

        if (! file_exists($path)) {
            $this->command?->error("Missing seed file: {$path}");
            return;
        }

        $json = json_decode(file_get_contents($path), true);

        foreach ($json['data'] ?? [] as $index => $item) {
            $attrs = $item['attributes'] ?? [];
            $category = DailyTourCategory::query()
                ->where('slug', $attrs['tour_type'] ?? null)
                ->first();

            DailyTour::updateOrCreate(
                ['external_id' => $item['id']],
                array_merge($attrs, $category?->tourAttributes() ?? [], [
                    'is_active' => true,
                    'sort_order' => $index,
                ])
            );
        }
    }
}
