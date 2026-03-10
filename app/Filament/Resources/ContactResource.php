<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Contacts';
    protected static ?string $modelLabel = 'Contact';
    protected static ?string $pluralModelLabel = 'Contacts';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Message details')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('full_name')
                            ->label('Name'),
                        TextEntry::make('email')
                            ->copyable(),
                        TextEntry::make('message')
                            ->columnSpanFull(),
                        TextEntry::make('ip_address')
                            ->label('IP address')
                            ->placeholder('-'),
                        TextEntry::make('user_agent')
                            ->label('User agent')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Created at')
                            ->dateTime(),
                        TextEntry::make('read_at')
                            ->label('Read at')
                            ->dateTime()
                            ->placeholder('Unread'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('id')
                    ->sortable(),
                Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Columns\TextColumn::make('surname')
                    ->label('Surname')
                    ->searchable()
                    ->sortable(),
                Columns\TextColumn::make('email')
                    ->searchable(),
                Columns\TextColumn::make('message')
                    ->limit(80)
                    ->tooltip(fn (Contact $record): string => $record->message)
                    ->wrap(),
                Columns\TextColumn::make('read_at')
                    ->label('Read')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Read' : 'Unread')
                    ->color(fn ($state): string => $state ? 'success' : 'warning')
                    ->sortable(),
                Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filters\TernaryFilter::make('read_status')
                    ->label('Read status')
                    ->placeholder('All')
                    ->trueLabel('Read')
                    ->falseLabel('Unread')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('read_at'),
                        false: fn (Builder $query) => $query->whereNull('read_at'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Actions\ViewAction::make(),
                Actions\Action::make('markRead')
                    ->label('Mark as read')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Contact $record): bool => $record->read_at === null)
                    ->action(function (Contact $record): void {
                        $record->forceFill(['read_at' => now()])->save();
                    }),
                Actions\Action::make('markUnread')
                    ->label('Mark as unread')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->visible(fn (Contact $record): bool => $record->read_at !== null)
                    ->action(function (Contact $record): void {
                        $record->forceFill(['read_at' => null])->save();
                    }),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkAction::make('markSelectedAsRead')
                    ->label('Mark selected as read')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($records): void {
                        $records->each(function (Contact $record): void {
                            if ($record->read_at === null) {
                                $record->forceFill(['read_at' => now()])->save();
                            }
                        });
                    }),
                Actions\BulkAction::make('markSelectedAsUnread')
                    ->label('Mark selected as unread')
                    ->icon('heroicon-o-envelope')
                    ->action(function ($records): void {
                        $records->each(function (Contact $record): void {
                            if ($record->read_at !== null) {
                                $record->forceFill(['read_at' => null])->save();
                            }
                        });
                    }),
                Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::hasSuperAdminAccess()) {
            return null;
        }

        $unread = static::getModel()::query()
            ->whereNull('read_at')
            ->count();

        return $unread > 0 ? (string) $unread : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::hasSuperAdminAccess();
    }

    public static function canAccess(): bool
    {
        return static::hasSuperAdminAccess();
    }

    public static function canViewAny(): bool
    {
        return static::hasSuperAdminAccess();
    }

    public static function canView(Model $record): bool
    {
        return static::hasSuperAdminAccess();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return static::hasSuperAdminAccess();
    }

    public static function canDeleteAny(): bool
    {
        return static::hasSuperAdminAccess();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'view' => Pages\ViewContact::route('/{record}'),
        ];
    }

    protected static function hasSuperAdminAccess(): bool
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
