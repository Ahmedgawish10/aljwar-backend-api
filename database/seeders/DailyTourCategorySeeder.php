<?php

namespace Database\Seeders;

use App\Models\DailyTourCategory;
use Illuminate\Database\Seeder;

class DailyTourCategorySeeder extends Seeder
{
    public function run(): void
    {
        $path = collect([
            base_path('data/daily-tours/categories.json'),
            base_path('data/tour-categories.json'),
            storage_path('app/tour-categories.json'),
        ])->first(fn (string $file) => file_exists($file));

        if (! $path) {
            $this->command?->error('Missing seed file: data/daily-tours/categories.json');
            return;
        }

        $json = json_decode(file_get_contents($path), true);
        $items = $json['data'] ?? $json;
        $slugs = [];

        foreach ($items as $index => $item) {
            $attrs = $item['attributes'] ?? $item;
            $slugs[] = $attrs['slug'];

            DailyTourCategory::updateOrCreate(
                ['slug' => $attrs['slug']],
                [
                    'name' => $attrs['name'],
                    'description' => $attrs['description'] ?? null,
                    'image' => $attrs['image'] ?? null,
                    'icon' => $attrs['icon'] ?? null,
                    'is_active' => $attrs['is_active'] ?? true,
                    'sort_order' => $attrs['sort_order'] ?? $index,
                ]
            );
        }

        DailyTourCategory::query()
            ->whereNotIn('slug', $slugs)
            ->delete();
    }
}
