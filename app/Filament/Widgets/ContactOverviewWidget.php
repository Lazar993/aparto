<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AppliesDashboardTimeFilter;
use App\Models\Contact;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContactOverviewWidget extends StatsOverviewWidget
{
    use AppliesDashboardTimeFilter;

    protected static ?int $sort = 4;

    protected ?string $heading = 'Contacts';

    protected ?string $description = 'Global contact form statistics';

    protected function getStats(): array
    {
        $query = $this->applyDashboardTimeFilter(Contact::query());

        $unread = (clone $query)->whereNull('read_at')->count();
        $read = (clone $query)->whereNotNull('read_at')->count();
        $total = (clone $query)->count();

        return [
            Stat::make('Unread contacts', (string) $unread)
                ->description('Messages waiting for review')
                ->color($unread > 0 ? 'warning' : 'success'),
            Stat::make('Read contacts', (string) $read)
                ->description('Messages already reviewed')
                ->color('info'),
            Stat::make('Total contacts', (string) $total)
                ->description('All contact requests in this period')
                ->color('gray'),
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->hasRole('super_admin', 'admin')
            || $user->hasRole('super_admin', 'web');
    }
}
