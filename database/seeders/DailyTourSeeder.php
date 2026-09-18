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
        $items = $json['data'] ?? $json;
        $slugs = [];

        foreach ($items as $index => $item) {
            $attrs = $item['attributes'] ?? $item;
            $category = DailyTourCategory::query()
                ->where('slug', $attrs['tour_type'] ?? $attrs['category'] ?? null)
                ->first();

            $slugs[] = $attrs['slug'];

            DailyTour::updateOrCreate(
                ['slug' => $attrs['slug']],
                array_merge([
                    'external_id' => (string) ($item['id'] ?? $attrs['external_id'] ?? $attrs['slug']),
                    'slug' => $attrs['slug'],
                    'name' => $attrs['name'],
                    'destination' => $attrs['destination'],
                    'destination_label' => $attrs['destination_label'] ?? null,
                    'description' => $attrs['description'] ?? null,
                    'overview' => $attrs['overview'] ?? $attrs['description'] ?? null,
                    'image' => $attrs['image_url'] ?? $attrs['image'] ?? null,
                    'hero_image' => $attrs['hero_image'] ?? null,
                    'gallery' => $attrs['gallery'] ?? null,
                    'rating' => $attrs['rating'] ?? 0,
                    'review_count' => $attrs['review_count'] ?? 0,
                    'tour_code' => $attrs['tour_code'] ?? null,
                    'duration' => $attrs['duration'],
                    'duration_hours' => $attrs['duration_hours'] ?? 0,
                    'duration_key' => $attrs['duration_key'] ?? 'multiDay',
                    'run' => $attrs['run'] ?? null,
                    'group_size' => $attrs['group_size'] ?? null,
                    'best_seller' => $attrs['best_seller'] ?? false,
                    'pickup_time' => $attrs['pickup_time'] ?? null,
                    'languages' => $attrs['languages'] ?? null,
                    'highlights' => $attrs['highlights'] ?? null,
                    'itinerary' => $attrs['itinerary'] ?? null,
                    'inclusions' => $attrs['inclusions'] ?? null,
                    'exclusions' => $attrs['exclusions'] ?? null,
                    'cancellation_policy' => $attrs['cancellation_policy'] ?? null,
                    'info_voucher' => $attrs['info_voucher'] ?? null,
                    'price' => $attrs['price'] ?? ['amount' => 0, 'currency' => 'USD'],
                    'is_active' => $attrs['is_active'] ?? true,
                    'sort_order' => $attrs['sort_order'] ?? $index,
                ], $category?->tourAttributes() ?? [])
            );
        }

        DailyTour::query()
            ->whereNotIn('slug', $slugs)
            ->delete();
    }
}
