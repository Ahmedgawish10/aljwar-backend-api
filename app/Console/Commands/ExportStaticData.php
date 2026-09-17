<?php

namespace App\Console\Commands;

use App\Models\AboutUs;
use App\Models\DailyTour;
use App\Models\DailyTourCategory;
use App\Models\Flight;
use App\Models\HolidayPackage;
use App\Models\Hotel;
use App\Models\PopularDestination;
use App\Models\PopularDestinationCategory;
use App\Models\Transfer;
use App\Models\VisaDestination;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ExportStaticData extends Command
{
    protected $signature = 'data:export-static';

    protected $description = 'Export full DB records into data/* folders (id + attributes, same shape as transfers.json)';

    public function handle(): int
    {
        $this->exportTyped('airport-transfers', 'data.json', Transfer::query()->orderBy('sort_order')->orderBy('id')->get(), 'transfer');
        $this->exportTyped('hotels', 'data.json', Hotel::query()->orderBy('sort_order')->orderBy('id')->get(), 'hotel');
        $this->exportTyped('flights', 'data.json', Flight::query()->orderBy('sort_order')->orderBy('id')->get(), 'flight');
        $this->exportTyped('holiday-packages', 'data.json', HolidayPackage::query()->orderBy('sort_order')->orderBy('id')->get(), 'holiday-package');
        $this->exportTyped('popular-destinations', 'data.json', PopularDestination::query()->orderBy('sort_order')->orderBy('id')->get(), 'destination');
        $this->exportTyped('daily-tours', 'data.json', DailyTour::query()->orderBy('sort_order')->orderBy('id')->get(), 'tour');
        $this->exportTyped('visa-services', 'data.json', VisaDestination::query()->orderBy('sort_order')->orderBy('id')->get(), 'visa');

        $this->exportPlain('daily-tours', 'categories.json', DailyTourCategory::query()->orderBy('sort_order')->orderBy('name')->get());
        $this->exportPlain('popular-destinations', 'categories.json', PopularDestinationCategory::query()->orderBy('sort_order')->orderBy('name')->get());

        $about = AboutUs::query()->first();
        if ($about) {
            $this->write('about-us', 'data.json', [
                'data' => $this->attributesOnly($about),
            ]);
        }

        // Keep book-now form schema from existing seed file if present
        $bookPath = base_path('data/book.json');
        if (is_file($bookPath)) {
            $dir = base_path('data/book-now');
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            copy($bookPath, $dir.'/data.json');
            $this->info('Wrote data/book-now/data.json (from book.json)');
        }

        // Also refresh root seed files used by seeders (same full shape)
        $this->syncRootSeed('transfers.json', Transfer::query()->orderBy('sort_order')->orderBy('id')->get(), 'transfer', withImageAlias: true);
        $this->syncRootSeed('hotels.json', Hotel::query()->orderBy('sort_order')->orderBy('id')->get(), 'hotel');
        $this->syncRootSeed('flights.json', Flight::query()->orderBy('sort_order')->orderBy('id')->get(), 'flight');
        $this->syncRootSeed('holiday-packages.json', HolidayPackage::query()->orderBy('sort_order')->orderBy('id')->get(), 'holiday-package');
        $this->syncRootSeed('destinations.json', PopularDestination::query()->orderBy('sort_order')->orderBy('id')->get(), 'destination');
        $this->syncRootSeed('tours.json', DailyTour::query()->orderBy('sort_order')->orderBy('id')->get(), 'tour');
        $this->syncRootSeed('tour-details.json', DailyTour::query()->orderBy('sort_order')->orderBy('id')->get(), 'tour');

        $this->writeRoot('tour-categories.json', [
            'data' => DailyTourCategory::query()->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn ($m) => $this->attributesOnly($m))
                ->values()
                ->all(),
        ]);
        $this->writeRoot('destination-categories.json', [
            'data' => PopularDestinationCategory::query()->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn ($m) => $this->attributesOnly($m))
                ->values()
                ->all(),
        ]);

        $this->info('Done. Full DB data exported into data/* folders.');

        return self::SUCCESS;
    }

    private function exportTyped(string $folder, string $file, Collection $items, string $type, bool $withImageAlias = true): void
    {
        $payload = [
            'data' => $items->map(fn (Model $model) => $this->typedItem($model, $type, $withImageAlias))->values()->all(),
        ];

        $this->write($folder, $file, $payload);
    }

    private function exportPlain(string $folder, string $file, Collection $items): void
    {
        $payload = [
            'data' => $items->map(fn (Model $model) => $this->attributesOnly($model))->values()->all(),
        ];

        $this->write($folder, $file, $payload);
    }

    private function syncRootSeed(string $file, Collection $items, string $type, bool $withImageAlias = false): void
    {
        $existingMeta = null;
        $path = base_path('data/'.$file);
        if (is_file($path)) {
            $existing = json_decode(file_get_contents($path), true);
            $existingMeta = $existing['meta'] ?? null;
        }

        $payload = [
            'data' => $items->map(fn (Model $model) => $this->typedItem($model, $type, $withImageAlias))->values()->all(),
        ];

        if ($existingMeta) {
            $payload = ['meta' => $existingMeta] + $payload;
        }

        $this->writeRoot($file, $payload);
    }

    private function typedItem(Model $model, string $type, bool $withImageAlias = false): array
    {
        $attrs = $this->attributesOnly($model);

        if ($withImageAlias && ! isset($attrs['image']) && isset($attrs['main_image'])) {
            $attrs['image'] = $attrs['main_image'];
        }

        if ($model instanceof Transfer && isset($attrs['duration_minutes']) && ! isset($attrs['duration'])) {
            $attrs['duration'] = $attrs['duration_minutes'].' Minutes';
        }

        return [
            'type' => $type,
            'id' => $model->getAttribute('country_name')
                ?? $model->getAttribute('external_id')
                ?? $model->getAttribute('slug')
                ?? (string) $model->getKey(),
            'attributes' => $attrs,
        ];
    }

    private function attributesOnly(Model $model): array
    {
        $hidden = [
            'id',
            'created_at',
            'updated_at',
            'external_id',
            'daily_tour_category_id',
        ];

        $attrs = [];
        foreach ($model->getAttributes() as $key => $raw) {
            if (in_array($key, $hidden, true)) {
                continue;
            }

            $attrs[$key] = $model->hasCast($key) || array_key_exists($key, $model->getCasts())
                ? $model->getAttribute($key)
                : $raw;
        }

        return $attrs;
    }

    private function write(string $folder, string $file, array $payload): void
    {
        $dir = base_path('data/'.$folder);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir.'/'.$file;
        file_put_contents(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n"
        );

        $this->info("Wrote data/{$folder}/{$file} (".count($payload['data'] ?? []).' items)');
    }

    private function writeRoot(string $file, array $payload): void
    {
        $path = base_path('data/'.$file);
        file_put_contents(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n"
        );

        $this->info("Wrote data/{$file}");
    }
}
