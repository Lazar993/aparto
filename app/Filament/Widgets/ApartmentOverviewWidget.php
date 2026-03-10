<?php

namespace App\Filament\Widgets;

use App\Models\Apartment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApartmentOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Apartments';

    protected function getDescription(): ?string
    {
        return $this->isViewingGlobalStats() ? 'Global apartment statistics' : 'Your apartment statistics';
    }

    protected function getStats(): array
    {
        $query = Apartment::query();

        if (! $this->isViewingGlobalStats()) {
            $query->where('user_id', auth()->id());
        }

        $total = (clone $query)->count();
        $active = (clone $query)->where('active', true)->count();
        $newThisMonth = (clone $query)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            Stat::make('Total apartments', (string) $total)
                ->description('All apartment listings')
                ->color('gray'),
            Stat::make('Active apartments', (string) $active)
                ->description('Currently visible listings')
                ->color('success'),
            Stat::make('New this month', (string) $newThisMonth)
                ->description('Recently added apartments')
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
