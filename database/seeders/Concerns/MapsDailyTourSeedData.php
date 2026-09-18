<?php

namespace Database\Seeders\Concerns;

trait MapsDailyTourSeedData
{
    protected function packageSourceDir(): string
    {
        return base_path('data-daily-category and trips');
    }

    protected function packageSourceFile(): string
    {
        return $this->packageSourceDir().DIRECTORY_SEPARATOR.'tour-packages.json';
    }

    protected function loadPackageCategories(): array
    {
        $path = $this->packageSourceFile();

        if (! file_exists($path)) {
            return [];
        }

        $json = json_decode((string) file_get_contents($path), true);

        return $json['categories'] ?? [];
    }

    protected function loadTourDetails(string $slug): array
    {
        $path = $this->packageSourceDir().DIRECTORY_SEPARATOR.$slug.'.json';

        if (! file_exists($path)) {
            return [];
        }

        $json = json_decode((string) file_get_contents($path), true);

        return is_array($json) ? $json : [];
    }

    protected function take(array $items, int $limit = 4): array
    {
        return array_values(array_slice($items, 0, $limit));
    }

    protected function shorten(?string $text, int $max = 180): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $text) ?? '');

        if ($text === '') {
            return null;
        }

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        if (preg_match('/^(.+?[.!?])(\s|$)/u', $text, $match) && mb_strlen($match[1]) <= $max) {
            return $match[1];
        }

        $cut = mb_substr($text, 0, $max);
        $comma = mb_strrpos($cut, ',');
        $space = mb_strrpos($cut, ' ');
        $break = ($comma !== false && $comma > 40) ? $comma : $space;

        if ($break !== false && $break > 30) {
            $cut = mb_substr($cut, 0, $break);
        }

        return rtrim($cut, ' ,;:-').'.';
    }

    protected function durationHours(array $tour): int
    {
        if (! empty($tour['duration_hours'])) {
            return (int) $tour['duration_hours'];
        }

        if (! empty($tour['days'])) {
            return (int) $tour['days'] * 24;
        }

        if (preg_match('/(\d+)/', (string) ($tour['duration'] ?? ''), $match)) {
            return (int) $match[1] * 24;
        }

        return 0;
    }

    protected function compactList(?array $items, int $limit = 4, int $textMax = 90): array
    {
        $items = array_values(array_filter($items ?? [], fn ($item) => is_string($item) && trim($item) !== ''));

        return array_map(
            fn (string $item) => $this->shorten($item, $textMax) ?? $item,
            $this->take($items, $limit)
        );
    }

    protected function compactItinerary(?array $items, int $limit = 8): array
    {
        $steps = [];

        foreach ($this->take($items ?? [], $limit) as $step) {
            if (! is_array($step)) {
                continue;
            }

            $title = trim((string) ($step['title'] ?? ''));
            $title = preg_replace('/^Day\s+\d+\s+/i', '', $title) ?? $title;

            $steps[] = [
                'time' => $step['time'] ?? ('Day '.(count($steps) + 1)),
                'title' => $this->shorten($title !== '' ? $title : ($step['time'] ?? 'Tour day'), 70) ?? $title,
                'icon' => $step['icon'] ?? 'map',
                'description' => $this->shorten($step['description'] ?? null, 220),
            ];
        }

        return $steps;
    }

    protected function withDetailDefaults(array $attrs): array
    {
        $overview = $attrs['overview'] ?? $attrs['description'] ?? null;
        $description = $attrs['description'] ?? $attrs['overview'] ?? null;
        $itinerary = $this->compactItinerary($attrs['itinerary'] ?? null);
        $highlights = $this->compactList($attrs['highlights'] ?? null, 6, 70);

        if ($highlights === []) {
            $highlights = $this->compactList(array_column($itinerary, 'title'), 6, 70);
        }

        if ($itinerary === []) {
            $itinerary = $this->fallbackItinerary($attrs, $overview);
        }

        if ($highlights === []) {
            $highlights = $this->compactList(array_filter([
                $attrs['destination_label'] ?? null,
                $attrs['duration'] ?? null,
                'Expert local guide',
                'Hotel pickup and transfers',
            ]), 4, 70);
        }

        $attrs['overview'] = $this->shorten($overview, 280) ?? $overview;
        $attrs['description'] = $this->shorten($description, 220) ?? $description;
        $attrs['highlights'] = $highlights;
        $attrs['itinerary'] = $itinerary;
        $attrs['inclusions'] = $this->compactList($attrs['inclusions'] ?? null, 6, 90) ?: [
            'Hotel pickup and drop-off',
            'English-speaking guide',
            'Entry fees as per itinerary',
            'Air-conditioned transfers',
        ];
        $attrs['exclusions'] = $this->compactList($attrs['exclusions'] ?? null, 4, 80) ?: [
            'Visa to Egypt',
            'International flights',
            'Optional tours',
            'Tipping',
        ];
        $attrs['languages'] = $attrs['languages'] ?? 'English';
        $attrs['group_size'] = $attrs['group_size'] ?? 'Private';
        $attrs['run'] = $attrs['run'] ?? 'Daily';

        return $attrs;
    }

    protected function fallbackItinerary(array $attrs, ?string $overview): array
    {
        $days = (int) ($attrs['days'] ?? 0);

        if ($days < 1 && preg_match('/(\d+)/', (string) ($attrs['duration'] ?? ''), $match)) {
            $days = (int) $match[1];
        }

        $days = max(1, min($days ?: 2, 6));
        $place = $attrs['destination_label'] ?? $attrs['name'] ?? 'Egypt';
        $blurb = $this->shorten($overview, 160) ?? 'Guided sightseeing with hotel transfers.';

        $steps = [[
            'time' => 'Day 1',
            'title' => 'Arrival',
            'icon' => 'flight',
            'description' => 'Meet and assist on arrival, then transfer to your hotel in '.$place.'.',
        ]];

        for ($day = 2; $day < $days; $day++) {
            $steps[] = [
                'time' => 'Day '.$day,
                'title' => 'Sightseeing',
                'icon' => 'map',
                'description' => $blurb,
            ];
        }

        if ($days > 1) {
            $steps[] = [
                'time' => 'Day '.$days,
                'title' => 'Departure',
                'icon' => 'flight',
                'description' => 'Breakfast at the hotel, then transfer for your final departure.',
            ];
        }

        return $steps;
    }

    protected function httpGallery(array $tour): array
    {
        $gallery = $tour['gallery_urls'] ?? [];

        if (! is_array($gallery) || $gallery === []) {
            $gallery = array_values(array_filter([
                $tour['image_url'] ?? null,
            ]));
        }

        return $this->take(array_values(array_filter($gallery, fn ($url) => is_string($url) && str_starts_with($url, 'http'))), 4);
    }
}
