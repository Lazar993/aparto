<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HostRequestResource\Pages;
use App\Models\HostRequest;
use App\Models\User;
use App\Notifications\HostApproved;
use App\Notifications\HostRejected;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class HostRequestResource extends Resource
{
    protected static ?string $model = HostRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = 'Host Requests';
    protected static ?string $modelLabel = 'Host Request';
    protected static ?string $pluralModelLabel = 'Host Requests';
    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->hasRole('super_admin') || $user->hasRole('admin'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Request details')
                    ->schema([
                        TextEntry::make('id')->label('ID'),
                        TextEntry::make('name'),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('phone')->copyable(),
                        TextEntry::make('city'),
                        TextEntry::make('listing_url')
                            ->label('Booking / Airbnb link')
                            ->url(fn (HostRequest $record): ?string => $record->listing_url)
                            ->openUrlInNewTab()
                            ->placeholder('-'),
                        TextEntry::make('number_of_apartments')
                            ->label('Number of apartments')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'warning',
                            }),
                        TextEntry::make('createdUser.name')
                            ->label('Created user')
                            ->placeholder('-'),
                        TextEntry::make('created_at')->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('city')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('number_of_apartments')
                    ->label('Apartments')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve host request')
                    ->modalDescription('This will create a host account and send a welcome email with a password reset link. Continue?')
                    ->visible(fn (HostRequest $record): bool => $record->isPending())
                    ->action(function (HostRequest $record): void {
                        $existingUser = User::where('email', $record->email)->first();

                        if ($existingUser) {
                            Notification::make()
                                ->title('A user with this email already exists.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $user = User::create([
                            'name' => $record->name,
                            'email' => $record->email,
                            'password' => Hash::make(Str::random(32)),
                            'user_type' => User::TYPE_HOST,
                        ]);

                        $token = Password::broker()->createToken($user);

                        $record->update([
                            'status' => HostRequest::STATUS_APPROVED,
                            'created_user_id' => $user->id,
                        ]);

                        $user->notify((new HostApproved($token))->locale($record->locale ?? 'sr'));

                        Notification::make()
                            ->title('Host approved and welcome email sent.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (HostRequest $record): bool => $record->isPending())
                    ->action(function (HostRequest $record): void {
                        $record->update(['status' => HostRequest::STATUS_REJECTED]);

                        \Illuminate\Support\Facades\Notification::route('mail', $record->email)
                            ->notify((new HostRejected($record->name))->locale($record->locale ?? 'sr'));

                        Notification::make()
                            ->title('Host request rejected.')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (HostRequest $record): bool => !$record->isApproved()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostRequests::route('/'),
            'view' => Pages\ViewHostRequest::route('/{record}'),
        ];
    }
}
