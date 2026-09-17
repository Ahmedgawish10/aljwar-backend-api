<?php

namespace Database\Seeders;

use App\Models\HolidayPackage;
use Illuminate\Database\Seeder;

class HolidayPackageSeeder extends Seeder
{
    public function run(): void
    {
        $path = collect([
            base_path('data/holiday-packages/data.json'),
            base_path('data/holiday-packages.json'),
            storage_path('app/holiday-packages.json'),
        ])->first(fn (string $file) => file_exists($file));

        if (! $path) {
            $this->command?->error('Missing seed file: data/holiday-packages/data.json');
            return;
        }

        $json = json_decode(file_get_contents($path), true);

        foreach ($json['data'] ?? [] as $index => $item) {
            HolidayPackage::updateOrCreate(
                ['external_id' => $item['id']],
                array_merge($item['attributes'] ?? [], [
                    'is_active' => true,
                    'sort_order' => $index,
                ])
            );
        }
    }
}
