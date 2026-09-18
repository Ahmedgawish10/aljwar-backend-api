<?php

namespace Database\Seeders;

use App\Models\DailyTourCategory;
use Database\Seeders\Concerns\MapsDailyTourSeedData;
use Illuminate\Database\Seeder;

class DailyTourCategorySeeder extends Seeder
{
    use MapsDailyTourSeedData;

    public function run(): void
    {
        $categories = $this->loadPackageCategories();

        if ($categories === []) {
            $this->command?->error('Missing seed file: data-daily-category and trips/tour-packages.json');
            return;
        }

        $slugs = [];

        foreach ($categories as $index => $item) {
            $slugs[] = $item['slug'];

            DailyTourCategory::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $this->shorten($item['description'] ?? $item['overview'] ?? null, 220),
                    'image' => $item['image_url'] ?? $item['image'] ?? null,
                    'icon' => $item['icon'] ?? null,
                    'is_active' => true,
                    'sort_order' => $item['sort_order'] ?? $index,
                ]
            );
        }

        DailyTourCategory::query()
            ->whereNotIn('slug', $slugs)
            ->delete();
    }
}
