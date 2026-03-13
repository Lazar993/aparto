<?php

namespace App\Filament\Widgets\Concerns;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

trait AppliesDashboardTimeFilter
{
    use InteractsWithPageFilters;

    protected function applyDashboardTimeFilter(Builder $query, string $column = 'created_at'): Builder
    {
        $range = $this->filters['time_range'] ?? 'all_time';

        return match ($range) {
            'last_day' => $query->where($column, '>=', now()->subDay()),
            'last_3_days' => $query->where($column, '>=', now()->subDays(3)),
            'last_7_days' => $query->where($column, '>=', now()->subDays(7)),
            'last_month' => $query->where($column, '>=', now()->subMonth()),
            default => $query,
        };
    }
}
