<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BookNowTab
{
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%' . trim($term) . '%';
                $q->where(function (Builder $inner) use ($like) {
                    foreach ($this->searchColumns() as $column) {
                        $inner->orWhere($column, 'like', $like);
                    }
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v));
    }

    abstract protected function searchColumns(): array;

    protected function dateValue(mixed $value): ?string
    {
        return optional($value)?->format('Y-m-d');
    }
}
