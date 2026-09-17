<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightBooking extends Model
{
    protected $fillable = [
        'flight_id',
        'external_id',
        'full_name',
        'email',
        'phone',
        'adults',
        'children',
        'nationality',
        'addons',
        'passengers_fare',
        'addons_total',
        'total_price',
        'currency',
        'status',
    ];

    protected $casts = [
        'addons' => 'array',
        'adults' => 'integer',
        'children' => 'integer',
        'passengers_fare' => 'float',
        'addons_total' => 'float',
        'total_price' => 'float',
    ];

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%' . trim($term) . '%';
                $q->where(function (Builder $inner) use ($like) {
                    $inner->where('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('external_id', 'like', $like);
                });
            })
            ->when($filters['external_id'] ?? null, fn ($q, $v) => $q->where('external_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['email'] ?? null, fn ($q, $v) => $q->where('email', $v));
    }
}
