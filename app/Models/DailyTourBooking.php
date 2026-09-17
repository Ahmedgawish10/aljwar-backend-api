<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyTourBooking extends Model
{
    protected $fillable = [
        'daily_tour_id',
        'slug',
        'tour_date',
        'adults',
        'children',
        'infants',
        'full_name',
        'email',
        'phone',
        'nationality',
        'adults_total',
        'children_total',
        'infants_total',
        'total_price',
        'currency',
        'status',
    ];

    protected $casts = [
        'tour_date' => 'date',
        'adults' => 'integer',
        'children' => 'integer',
        'infants' => 'integer',
        'adults_total' => 'float',
        'children_total' => 'float',
        'infants_total' => 'float',
        'total_price' => 'float',
    ];

    public function dailyTour(): BelongsTo
    {
        return $this->belongsTo(DailyTour::class);
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
