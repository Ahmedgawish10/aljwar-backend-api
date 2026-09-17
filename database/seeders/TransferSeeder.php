<?php

namespace Database\Seeders;

use App\Models\Transfer;
use Illuminate\Database\Seeder;

class TransferSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('data/transfers.json');

        if (! file_exists($path)) {
            $this->command?->error("Missing seed file: {$path}");
            return;
        }

        $json = json_decode(file_get_contents($path), true);

        foreach ($json['data'] ?? [] as $index => $item) {
            $attrs = $item['attributes'] ?? [];

            if (is_array($attrs['description'] ?? null)) {
                $attrs['description'] = implode("\n\n", $attrs['description']);
            }

            $attrs['main_image'] = $attrs['image'] ?? $attrs['main_image'] ?? null;
            unset($attrs['image'], $attrs['heroImage'], $attrs['hero_image'], $attrs['duration']);

            Transfer::updateOrCreate(
                ['external_id' => $item['id']],
                array_merge($attrs, [
                    'is_active' => true,
                    'sort_order' => $index,
                ])
            );
        }
    }
}
