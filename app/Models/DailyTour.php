<?php

namespace App\Models;

use App\Models\Concerns\HasMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class DailyTour extends Model
{
    use HasMediaUrl;

    protected $fillable = [
        'daily_tour_category_id',
        'external_id',
        'slug',
        'name',
        'destination',
        'destination_label',
        'description',
        'overview',
        'image',
        'hero_image',
        'gallery',
        'rating',
        'review_count',
        'tour_type',
        'tour_type_label',
        'tour_code',
        'duration',
        'duration_hours',
        'duration_key',
        'run',
        'group_size',
        'best_seller',
        'pickup_time',
        'languages',
        'highlights',
        'itinerary',
        'inclusions',
        'exclusions',
        'cancellation_policy',
        'info_voucher',
        'price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'gallery' => 'array',
        'highlights' => 'array',
        'itinerary' => 'array',
        'inclusions' => 'array',
        'exclusions' => 'array',
        'price' => 'array',
        'best_seller' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'float',
        'review_count' => 'integer',
        'duration_hours' => 'integer',
    ];

    public const LIST_COLUMNS = [
        'id',
        'daily_tour_category_id',
        'external_id',
        'slug',
        'name',
        'destination',
        'destination_label',
        'description',
        'image',
        'gallery',
        'rating',
        'review_count',
        'tour_type',
        'duration',
        'duration_hours',
        'duration_key',
        'run',
        'group_size',
        'best_seller',
        'price',
        'highlights',
        'itinerary',
        'inclusions',
        'exclusions',
        'sort_order',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DailyTourCategory::class, 'daily_tour_category_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(DailyTourBooking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForList(Builder $query): Builder
    {
        return $query->active()->with('category')->select(self::LIST_COLUMNS);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.trim($term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('destination_label', 'like', $like)
                ->orWhere('destination', 'like', $like)
                ->orWhere('slug', 'like', $like);
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->search($term))
            ->when($filters['destination'] ?? null, fn ($q, $value) => $q->whereIn('destination', $this->toList($value)))
            ->when(
                $filters['category'] ?? $filters['tour_type'] ?? null,
                fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug))
            )
            ->when($filters['duration_key'] ?? null, fn ($q, $value) => $q->whereIn('duration_key', $this->toList($value)))
            ->when($filters['min_rating'] ?? null, fn ($q, $v) => $q->where('rating', '>=', (float) $v))
            ->when($filters['min_price'] ?? null, fn ($q, $v) => $q->where('price->amount', '>=', (float) $v))
            ->when($filters['max_price'] ?? null, fn ($q, $v) => $q->where('price->amount', '<=', (float) $v))
            ->when(isset($filters['best_seller']), function ($q) use ($filters) {
                $q->where('best_seller', filter_var($filters['best_seller'], FILTER_VALIDATE_BOOLEAN));
            });
    }

    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'priceLow' => $query->orderBy(DB::raw('JSON_EXTRACT(price, "$.amount") + 0'), 'asc'),
            'priceHigh' => $query->orderBy(DB::raw('JSON_EXTRACT(price, "$.amount") + 0'), 'desc'),
            'rating' => $query->orderByDesc('rating'),
            default => $query->orderByDesc('id'),
        };
    }

    public function pricePayload(): array
    {
        $price = $this->price ?? [];

        $payload = [
            'amount' => (float) ($price['amount'] ?? 0),
            'currency' => $price['currency'] ?? 'USD',
        ];

        if (isset($price['child_amount'])) {
            $payload['child_amount'] = (float) $price['child_amount'];
        }

        if (isset($price['infant_amount'])) {
            $payload['infant_amount'] = (float) $price['infant_amount'];
        }

        return $payload;
    }

    private function toList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }
}
