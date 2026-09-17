<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Transfer extends Model
{
    protected $fillable = [
        'external_id',
        'slug',
        'name',
        'destination',
        'destination_label',
        'duration_minutes',
        'experience',
        'price',
        'main_image',
        'run',
        'group_size',
        'gallery',
        'description',
        'inclusions',
        'exclusions',
        'meeting_point',
        'things_to_remember',
        'cancellation_policy',
        'info_voucher',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'array',
        'gallery' => 'array',
        'inclusions' => 'array',
        'exclusions' => 'array',
        'things_to_remember' => 'array',
        'is_active' => 'boolean',
        'duration_minutes' => 'integer',
    ];

    public const LIST_COLUMNS = [
        'id',
        'external_id',
        'slug',
        'name',
        'destination',
        'destination_label',
        'duration_minutes',
        'experience',
        'price',
        'main_image',
        'gallery',
        'inclusions',
        'exclusions',
        'meeting_point',
        'things_to_remember',
        'sort_order',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(TransferBooking::class);
    }

    public function descriptionText(): ?string
    {
        $value = $this->description;

        return is_array($value)
            ? (implode("\n\n", array_filter($value)) ?: null)
            : ($value ?: null);
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

    public function pricePayload(): array
    {
        $price = $this->price ?? [];
        $amount = (float) ($price['amount'] ?? 0);

        return [
            'amount' => $amount,
            'currency' => $price['currency'] ?? 'USD',
            'formatted' => $price['formatted'] ?? ('$ '.number_format($amount, 0)),
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

        $like = '%'.trim($term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('destination', 'like', $like)
                ->orWhere('destination_label', 'like', $like)
                ->orWhere('slug', 'like', $like);
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $list = fn ($value) => is_array($value)
            ? $value
            : array_filter(array_map('trim', explode(',', (string) $value)));

        return $query
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->search($term))
            ->when($filters['destination'] ?? null, fn ($q, $value) => $q->whereIn('destination', $list($value)))
            ->when($filters['experience'] ?? null, fn ($q, $value) => $q->whereIn('experience', $list($value)))
            ->when($filters['min_price'] ?? null, fn ($q, $v) => $q->where('price->amount', '>=', (float) $v))
            ->when($filters['max_price'] ?? null, fn ($q, $v) => $q->where('price->amount', '<=', (float) $v));
    }

    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        $price = DB::raw('JSON_EXTRACT(price, "$.amount") + 0');

        return match ($sort) {
            'priceLow' => $query->orderBy($price),
            'priceHigh' => $query->orderByDesc($price),
            'popularity' => $query->orderBy('name'),
            default => $query->orderByDesc('id'),
        };
    }
}
