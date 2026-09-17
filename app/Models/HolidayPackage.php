<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HolidayPackage extends Model
{
    protected $fillable = [
        'external_id',
        'slug',
        'name',
        'location',
        'destination',
        'duration',
        'days',
        'nights',
        'price_from',
        'currency',
        'rating',
        'reviews',
        'tour_type',
        'group_size',
        'badge',
        'categories',
        'features',
        'image',
        'gallery',
        'overview',
        'inclusions_bar',
        'itinerary',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'categories' => 'array',
        'features' => 'array',
        'gallery' => 'array',
        'inclusions_bar' => 'array',
        'itinerary' => 'array',
        'is_active' => 'boolean',
        'days' => 'integer',
        'nights' => 'integer',
        'price_from' => 'integer',
        'rating' => 'float',
        'reviews' => 'integer',
    ];

    public const LIST_COLUMNS = [
        'id',
        'external_id',
        'slug',
        'name',
        'location',
        'destination',
        'duration',
        'price_from',
        'currency',
        'badge',
        'categories',
        'features',
        'image',
        'itinerary',
        'sort_order',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(HolidayPackageBooking::class);
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

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%' . trim($term) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('location', 'like', $like)
                ->orWhere('destination', 'like', $like)
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
                $q->whereJsonContains('categories', $value);
            })
            ->when($filters['destination'] ?? null, fn ($q, $v) => $q->where('destination', $v))
            ->when($filters['min_price'] ?? null, fn ($q, $v) => $q->where('price_from', '>=', (int) $v))
            ->when($filters['max_price'] ?? null, fn ($q, $v) => $q->where('price_from', '<=', (int) $v));
    }

    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'priceLow' => $query->orderBy('price_from', 'asc'),
            'priceHigh' => $query->orderByDesc('price_from'),
            default => $query->orderByDesc('id'),
        };
    }
}
