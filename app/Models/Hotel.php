<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Hotel extends Model
{
    protected $fillable = [
        'external_id',
        'slug',
        'name',
        'location',
        'destination',
        'description',
        'main_image',
        'hero_image',
        'gallery',
        'stars',
        'rating',
        'review_label_key',
        'review_count',
        'review_breakdown',
        'property_type',
        'best_seller',
        'amenities',
        'price',
        'check_in',
        'check_out',
        'pets',
        'things_to_remember',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'gallery' => 'array',
        'review_breakdown' => 'array',
        'amenities' => 'array',
        'price' => 'array',
        'things_to_remember' => 'array',
        'best_seller' => 'boolean',
        'is_active' => 'boolean',
        'stars' => 'integer',
        'rating' => 'float',
        'review_count' => 'integer',
    ];

    /** Minimal fields for hotel list cards. */
    public const LIST_COLUMNS = [
        'id',
        'external_id',
        'slug',
        'name',
        'location',
        'destination',
        'description',
        'main_image',
        'stars',
        'rating',
        'review_label_key',
        'review_count',
        'property_type',
        'best_seller',
        'amenities',
        'price',
        'sort_order',
    ];

    public function descriptionText(): ?string
    {
        $value = $this->getAttributes()['description'] ?? null;

        if (is_array($value)) {
            return implode("\n\n", array_filter($value));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return implode("\n\n", array_filter($decoded));
            }
        }

        return $value ?: null;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class);
    }

    public function storageFolder(): string
    {
        return 'hotels/' . $this->slug;
    }

    public function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return url('storage/'.$path);
    }

    public function galleryUrls(): array
    {
        return array_values(array_filter(array_map(
            fn (?string $path) => $this->mediaUrl($path),
            $this->gallery ?? []
        )));
    }

    protected static function booted(): void
    {
        static::deleting(function (Hotel $hotel) {
            Storage::disk('public')->deleteDirectory($hotel->storageFolder());
        });
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
            ->when($filters['destination'] ?? null, function ($q, $value) {
                $list = is_array($value)
                    ? $value
                    : array_filter(array_map('trim', explode(',', $value)));
                $q->whereIn('destination', $list);
            })
            ->when($filters['stars'] ?? null, function ($q, $value) {
                $list = is_array($value)
                    ? $value
                    : array_filter(array_map('trim', explode(',', $value)));
                $q->whereIn('stars', array_map('intval', $list));
            })
            ->when($filters['property_type'] ?? null, function ($q, $value) {
                $list = is_array($value)
                    ? $value
                    : array_filter(array_map('trim', explode(',', $value)));
                $q->whereIn('property_type', $list);
            })
            ->when($filters['amenities'] ?? null, function ($q, $value) {
                $list = is_array($value)
                    ? $value
                    : array_filter(array_map('trim', explode(',', $value)));
                foreach ($list as $amenity) {
                    $q->whereJsonContains('amenities', $amenity);
                }
            })
            ->when($filters['min_rating'] ?? null, fn ($q, $v) => $q->where('rating', '>=', (float) $v))
            ->when($filters['min_price'] ?? null, fn ($q, $v) => $q->where('price->amount', '>=', (float) $v))
            ->when($filters['max_price'] ?? null, fn ($q, $v) => $q->where('price->amount', '<=', (float) $v))
            ->when(isset($filters['best_seller']), function ($q) use ($filters) {
                $q->where('best_seller', filter_var($filters['best_seller'], FILTER_VALIDATE_BOOLEAN));
            });
    }

    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        $priceExpr = DB::raw('JSON_EXTRACT(price, "$.amount") + 0');
        return match ($sort) {
            'priceLow' => $query->orderBy($priceExpr, 'asc'),
            'priceHigh' => $query->orderBy($priceExpr, 'desc'),
            'rating' => $query->orderByDesc('rating'),
            default => $query->orderByDesc('id'),
        };
    }

    public function pricePayload(): array
    {
        $price = $this->price ?? [];
        $amount = (float) ($price['amount'] ?? 0);
        $currency = $price['currency'] ?? 'USD';

        return [
            'amount' => $amount,
            'currency' => $currency,
            'formatted' => $price['formatted'] ?? ('$' . number_format($amount, 0)),
        ];
    }
}
