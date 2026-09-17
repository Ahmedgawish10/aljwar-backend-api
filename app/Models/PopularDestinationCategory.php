<?php

namespace App\Models;

use App\Models\Concerns\HasMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PopularDestinationCategory extends Model
{
    use HasMediaUrl;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'image',
        'price_from',
        'currency',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price_from' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeListed(Builder $query): Builder
    {
        return $query->active()->orderByDesc('id');
    }

    public static function attachDestinationCounts(Collection $categories): Collection
    {
        $stats = PopularDestination::query()
            ->active()
            ->select(
                'country',
                DB::raw('count(*) as aggregate'),
                DB::raw('min(price_from) as min_price')
            )
            ->groupBy('country')
            ->get()
            ->keyBy('country');

        return $categories->each(function ($category) use ($stats) {
            $row = $stats->get($category->name);
            $category->destinations_count = (int) ($row->aggregate ?? 0);

            if ((int) $category->price_from === 0 && $row) {
                $category->price_from = (int) ($row->min_price ?? 0);
            }

            $category->currency = $category->currency ?: 'USD';
        });
    }
}
