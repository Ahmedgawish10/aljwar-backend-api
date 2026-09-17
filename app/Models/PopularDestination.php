<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PopularDestination extends Model
{
    protected $table = 'popular_destinations';

    protected $fillable = [
        'external_id',
        'slug',
        'name',
        'country',
        'categories',
        'price_from',
        'currency',
        'featured',
        'image',
        'gallery',
        'overview',
        'highlights',
        'info',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'categories' => 'array',
        'gallery' => 'array',
        'highlights' => 'array',
        'info' => 'array',
        'featured' => 'boolean',
        'is_active' => 'boolean',
        'price_from' => 'integer',
    ];

    public const LIST_COLUMNS = [
        'id',
        'external_id',
        'slug',
        'name',
        'country',
        'categories',
        'price_from',
        'currency',
        'featured',
        'image',
        'sort_order',
    ];

    public const CATEGORIES = ['beach', 'city', 'adventure', 'nature', 'culture'];

    public function bookings(): HasMany
    {
        return $this->hasMany(PopularDestinationBooking::class);
    }

    public function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return url($path);
        }

        return url('storage/'.$path);
    }

    public function pricePayload(): array
    {
        return [
            'amount' => (int) $this->price_from,
            'currency' => $this->currency ?? 'USD',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForList(Builder $query): Builder
    {
        return $query->active()->select(self::LIST_COLUMNS);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%' . trim($term) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('country', 'like', $like)
                ->orWhere('slug', 'like', $like);
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->search($term))
            ->when($filters['category'] ?? null, function ($q, $value) {
                if ($value === 'all') {
                    return;
                }

                $country = PopularDestinationCategory::query()
                    ->where('slug', $value)
                    ->value('name') ?? $value;

                $q->where('country', $country);
            })
            ->when($filters['min_price'] ?? null, fn ($q, $v) => $q->where('price_from', '>=', (int) $v))
            ->when($filters['max_price'] ?? null, fn ($q, $v) => $q->where('price_from', '<=', (int) $v))
            ->when(isset($filters['featured']), function ($q) use ($filters) {
                $q->where('featured', filter_var($filters['featured'], FILTER_VALIDATE_BOOLEAN));
            });
    }

    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'priceLow' => $query->orderBy('price_from', 'asc'),
            'priceHigh' => $query->orderByDesc('price_from'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('id'),
        };
    }
}
