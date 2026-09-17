<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    /**
     * Column names = frontend form keys (no mapping).
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'country',
        'subject',
        'service',
        'message',
        'privacy',
        'status',
        'locale',
    ];

    protected $casts = [
        'privacy' => 'boolean',
    ];

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%' . trim($term) . '%';
                $q->where(function (Builder $inner) use ($like) {
                    $inner->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('subject', 'like', $like)
                        ->orWhere('message', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['service'] ?? null, fn ($q, $v) => $q->where('service', $v))
            ->when($filters['country'] ?? null, fn ($q, $v) => $q->where('country', $v));
    }
}
