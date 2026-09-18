<?php

namespace Database\Seeders;

use App\Models\DailyTour;
use App\Models\DailyTourCategory;
use Database\Seeders\Concerns\MapsDailyTourSeedData;
use Illuminate\Database\Seeder;

class DailyTourSeeder extends Seeder
{
    use MapsDailyTourSeedData;

    public function run(): void
    {
        $categories = $this->loadPackageCategories();

        if ($categories === []) {
            $this->command?->error('Missing seed file: data-daily-category and trips/tour-packages.json');
            return;
        }

        $usedSlugs = [];
        $slugs = [];
        $index = 0;

        foreach ($categories as $categoryData) {
            $category = DailyTourCategory::query()
                ->where('slug', $categoryData['slug'] ?? null)
                ->first();

            foreach ($categoryData['tours'] ?? [] as $tour) {
                $details = $this->loadTourDetails($tour['slug'] ?? '');
                $attrs = $this->withDetailDefaults(array_merge($tour, $details));
                $slug = $this->uniqueSlug($attrs['slug'], $attrs['category'] ?? $categoryData['slug'], $usedSlugs);
                $usedSlugs[$slug] = true;
                $slugs[] = $slug;

                $gallery = $this->httpGallery($attrs);
                $image = $attrs['image_url'] ?? ($gallery[0] ?? null);

                DailyTour::updateOrCreate(
                    ['slug' => $slug],
                    array_merge([
                        'external_id' => $slug,
                        'slug' => $slug,
                        'name' => $attrs['name'],
                        'destination' => $this->destination($attrs),
                        'destination_label' => $attrs['destination_label'] ?? null,
                        'description' => $attrs['description'] ?? null,
                        'overview' => $attrs['overview'] ?? null,
                        'image' => $image,
                        'hero_image' => $attrs['hero_image'] ?? $image,
                        'gallery' => $gallery,
                        'rating' => $attrs['rating'] ?? 0,
                        'review_count' => $attrs['review_count'] ?? 0,
                        'tour_code' => isset($attrs['tour_code']) ? (string) $attrs['tour_code'] : null,
                        'duration' => $attrs['duration'] ?? 'Multi Day',
                        'duration_hours' => $this->durationHours($attrs),
                        'duration_key' => $attrs['duration_key'] ?? 'multiDay',
                        'run' => $attrs['run'] ?? 'Daily',
                        'group_size' => $attrs['group_size'] ?? 'Private',
                        'best_seller' => (bool) ($attrs['best_seller'] ?? false),
                        'pickup_time' => $attrs['pickup_time'] ?? null,
                        'languages' => $attrs['languages'] ?? 'English',
                        'highlights' => $attrs['highlights'],
                        'itinerary' => $attrs['itinerary'],
                        'inclusions' => $attrs['inclusions'],
                        'exclusions' => $attrs['exclusions'],
                        'cancellation_policy' => $attrs['cancellation_policy'] ?? 'Cancel up to 24 hours in advance for a full refund.',
                        'info_voucher' => $attrs['info_voucher'] ?? null,
                        'price' => $attrs['price'] ?? ['amount' => 0, 'currency' => 'USD'],
                        'is_active' => true,
                        'sort_order' => $index,
                    ], $category?->tourAttributes() ?? [])
                );

                $index++;
            }
        }

        DailyTour::query()
            ->whereNotIn('slug', $slugs)
            ->delete();
    }

    private function uniqueSlug(string $slug, ?string $category, array $usedSlugs): string
    {
        if (! isset($usedSlugs[$slug])) {
            return $slug;
        }

        return $slug.'-'.($category ?: 'tour');
    }

    private function destination(array $attrs): string
    {
        $destination = strtolower(trim((string) ($attrs['destination'] ?? '')));

        if ($destination !== '') {
            return mb_substr($destination, 0, 50);
        }

        $label = strtolower((string) ($attrs['destination_label'] ?? 'egypt'));
        $first = preg_split('/[^a-z]+/', $label)[0] ?? 'egypt';

        return mb_substr($first !== '' ? $first : 'egypt', 0, 50);
    }
}
