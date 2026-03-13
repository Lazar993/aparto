<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AppliesDashboardTimeFilter;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReviewOverviewWidget extends StatsOverviewWidget
{
    use AppliesDashboardTimeFilter;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Reviews';

    protected function getDescription(): ?string
    {
        return $this->isViewingGlobalStats() ? 'Global review statistics' : 'Your review statistics';
    }

    protected function getStats(): array
    {
        $query = Review::query();

        if (! $this->isViewingGlobalStats()) {
            $query->whereHas('apartment', function ($apartmentQuery): void {
                $apartmentQuery->where('user_id', auth()->id());
            });
        }

        $query = $this->applyDashboardTimeFilter($query);

        $total = (clone $query)->count();
        $approved = (clone $query)->where('status', 'approved')->count();
        $pending = (clone $query)->where('status', 'pending')->count();

        $avgRating = round((float) ((clone $query)->where('status', 'approved')->avg('rating') ?? 0), 2);

        return [
            Stat::make('Total reviews', (string) $total)
                ->description('All review records')
                ->color('gray'),
            Stat::make('Approved', (string) $approved)
                ->description('Visible approved reviews')
                ->color('success'),
            Stat::make('Pending', (string) $pending)
                ->description('Waiting for moderation')
                ->color('warning'),
            Stat::make('Average rating', number_format($avgRating, 2))
                ->description('Average of approved reviews')
                ->color('info'),
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isAdmin()
            || $user->isHost()
            || $user->hasRole('super_admin')
            || $user->hasRole('admin')
            || $user->hasRole('host')
            || $user->hasRole('admin', 'admin')
            || $user->hasRole('host', 'admin')
            || $user->hasRole('super_admin', 'admin')
            || $user->hasRole('admin', 'web')
            || $user->hasRole('host', 'web')
            || $user->hasRole('super_admin', 'web');
    }

    private function isViewingGlobalStats(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isAdmin()
            || $user->hasRole('super_admin')
            || $user->hasRole('admin')
            || $user->hasRole('super_admin', 'admin')
            || $user->hasRole('admin', 'admin')
            || $user->hasRole('super_admin', 'web')
            || $user->hasRole('admin', 'web');
    }
}
