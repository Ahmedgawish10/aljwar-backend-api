<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Flight extends Model
{
    protected $fillable = [
        'external_id',
        'airline',
        'airline_name',
        'airline_logo',
        'image',
        'image_alt',
        'hero_bg',
        'depart_time',
        'arrive_time',
        'depart_date',
        'arrive_date',
        'date_label',
        'arrive_next_day',
        'duration',
        'stops',
        'departure',
        'arrival',
        'depart_terminal',
        'arrive_terminal',
        'cabin',
        'departure_slot',
        'price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'array',
        'arrive_next_day' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** Minimal fields for flight list cards. */
    public const LIST_COLUMNS = [
        'id',
        'external_id',
        'airline',
        'airline_name',
        'airline_logo',
        'depart_time',
        'arrive_time',
        'duration',
        'stops',
        'departure',
        'arrival',
        'departure_slot',
        'price',
        'image',
        'sort_order',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(FlightBooking::class);
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
            $q->where('airline_name', 'like', $like)
                ->orWhere('airline', 'like', $like)
                ->orWhere('departure', 'like', $like)
                ->orWhere('arrival', 'like', $like)
                ->orWhere('external_id', 'like', $like);
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->search($term))
            ->when($filters['airline'] ?? null, function ($q, $value) {
                $list = is_array($value)
                    ? $value
                    : array_filter(array_map('trim', explode(',', $value)));
                $q->whereIn('airline', $list);
            })
            ->when($filters['stops'] ?? null, function ($q, $value) {
                $list = is_array($value)
                    ? $value
                    : array_filter(array_map('trim', explode(',', $value)));
                if (! in_array('all', $list, true)) {
                    $q->whereIn('stops', $list);
                }
            })
            ->when($filters['departure'] ?? null, fn ($q, $v) => $q->where('departure', $v))
            ->when($filters['arrival'] ?? null, fn ($q, $v) => $q->where('arrival', $v))
            ->when($filters['departure_slot'] ?? null, function ($q, $value) {
                $list = is_array($value)
                    ? $value
                    : array_filter(array_map('trim', explode(',', $value)));
                $q->whereIn('departure_slot', $list);
            })
            ->when($filters['min_price'] ?? null, fn ($q, $v) => $q->where('price->amount', '>=', (float) $v))
            ->when($filters['max_price'] ?? null, fn ($q, $v) => $q->where('price->amount', '<=', (float) $v));
    }

    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'priceLow' => $query->orderBy(DB::raw('JSON_EXTRACT(price, "$.amount") + 0'), 'asc'),
            'priceHigh' => $query->orderBy(DB::raw('JSON_EXTRACT(price, "$.amount") + 0'), 'desc'),
            'duration' => $query->orderBy('duration', 'asc'),
            default => $query->orderByDesc('id'),
        };
    }

    public function pricePayload(): array
    {
        $price = $this->price ?? [];

        return [
            'amount' => (float) ($price['amount'] ?? 0),
            'currency' => $price['currency'] ?? 'USD',
        ];
    }
}
