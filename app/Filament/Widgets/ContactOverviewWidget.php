<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContactOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Contacts';

    protected ?string $description = 'Global contact form statistics';

    protected function getStats(): array
    {
        $unread = Contact::query()->whereNull('read_at')->count();
        $today = Contact::query()->whereDate('created_at', today())->count();
        $total = Contact::query()->count();

        return [
            Stat::make('Unread contacts', (string) $unread)
                ->description('Messages waiting for review')
                ->color($unread > 0 ? 'warning' : 'success'),
            Stat::make('Received today', (string) $today)
                ->description('Contact messages from today')
                ->color('info'),
            Stat::make('Total contacts', (string) $total)
                ->description('All saved contact requests')
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
