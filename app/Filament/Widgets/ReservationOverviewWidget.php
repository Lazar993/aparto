<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AppliesDashboardTimeFilter;
use App\Models\Reservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReservationOverviewWidget extends StatsOverviewWidget
{
    use AppliesDashboardTimeFilter;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Reservations';

    protected function getDescription(): ?string
    {
        return $this->isViewingGlobalStats() ? 'Global reservation statistics' : 'Your reservation statistics';
    }

    protected function getStats(): array
    {
        $query = Reservation::query();

        if (! $this->isViewingGlobalStats()) {
            $query->whereHas('apartment', function ($apartmentQuery): void {
                $apartmentQuery->where('user_id', auth()->id());
            });
        }

        $query = $this->applyDashboardTimeFilter($query);

        $total = (clone $query)->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $confirmed = (clone $query)->where('status', 'confirmed')->count();

        $revenue = (float) (clone $query)
            ->where('status', 'confirmed')
            ->sum('total_price');

        return [
            Stat::make('Total reservations', (string) $total)
                ->description('All reservation records')
                ->color('gray'),
            Stat::make('Pending', (string) $pending)
                ->description('Waiting for confirmation')
                ->color('warning'),
            Stat::make('Confirmed', (string) $confirmed)
                ->description('Successfully confirmed reservations')
                ->color('success'),
            Stat::make('Confirmed revenue', sprintf('%s %.2f', config('website.currency'), $revenue))
                ->description('Sum of confirmed reservations')
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
