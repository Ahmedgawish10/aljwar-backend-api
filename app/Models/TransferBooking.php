<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferBooking extends Model
{
    /**
     * Column names = frontend booking keys (no mapping).
     */
    protected $fillable = [
        'transfer_id',
        'slug',
        'travel_date',
        'adults',
        'full_name',
        'email',
        'phone',
        'nationality',
        'unit_price',
        'total_price',
        'currency',
        'status',
    ];

    protected $casts = [
        'travel_date' => 'date:Y-m-d',
        'adults' => 'integer',
        'unit_price' => 'float',
        'total_price' => 'float',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
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
                        ->orWhere('slug', 'like', $like);
                });
            })
            ->when($filters['slug'] ?? null, fn ($q, $v) => $q->where('slug', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['email'] ?? null, fn ($q, $v) => $q->where('email', $v));
    }
}
