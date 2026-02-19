<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservationResource\Pages;
use App\Models\Reservation;
use Filament\Forms\{Form, Components};
use Filament\Resources\Resource;
use Filament\Tables\{Table, Actions, Columns, Filters};
use Illuminate\Database\Eloquent\Builder;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Select::make('apartment_id')
                    ->relationship('apartment', 'title')
                    ->searchable()
                    ->required(),
                Components\TextInput::make('name')
                    ->required()
                    ->maxLength(120),
                Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(160),
                Components\TextInput::make('phone')
                    ->required()
                    ->maxLength(40),
                Components\DatePicker::make('date_from')
                    ->required(),
                Components\DatePicker::make('date_to')
                    ->required(),
                Components\TextInput::make('nights')
                    ->numeric()
                    ->required(),
                Components\TextInput::make('price_per_night')
                    ->numeric()
                    ->required(),
                Components\TextInput::make('total_price')
                    ->numeric()
                    ->required(),
                Components\TextInput::make('deposit_amount')
                    ->numeric(),
                Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'canceled' => 'Canceled',
                    ])
                    ->required(),
                Components\TextInput::make('payment_provider')
                    ->maxLength(120),
                Components\TextInput::make('payment_reference')
                    ->maxLength(160),
                Components\DateTimePicker::make('paid_at'),
                Components\Textarea::make('note')
                    ->rows(3)
                    ->maxLength(1000),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('id')
                    ->sortable()
                    ->toggleable(),
                Columns\TextColumn::make('apartment.title')
                    ->label('Apartment')
                    ->searchable()
                    ->sortable(),
                Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Columns\TextColumn::make('email')
                    ->searchable(),
                Columns\TextColumn::make('phone')
                    ->searchable(),
                Columns\TextColumn::make('date_from')
                    ->date()
                    ->sortable(),
                Columns\TextColumn::make('date_to')
                    ->date()
                    ->sortable(),
                Columns\TextColumn::make('nights')
                    ->sortable(),
                Columns\TextColumn::make('total_price')
                    ->formatStateUsing(function ($state): string {
                        return sprintf('%s %0.2f', config('website.currency'), (float) $state);
                    })
                    ->sortable(),
                Columns\TextColumn::make('deposit_amount')
                    ->formatStateUsing(function ($state): string {
                        return sprintf('%s %0.2f', config('website.currency'), (float) $state);
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Columns\TextColumn::make('balance_due')
                    ->label('Balance Due')
                    ->getStateUsing(function (Reservation $record): float {
                        $deposit = $record->deposit_amount ?? 0;
                        return max(0, (float) $record->total_price - (float) $deposit);
                    })
                    ->formatStateUsing(function (float $state): string {
                        return sprintf('%s %0.2f', config('website.currency'), $state);
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'confirmed' => 'success',
                        'canceled' => 'danger',
                    })
                    ->sortable(),
                Columns\TextColumn::make('note')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'canceled' => 'Canceled',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('deleted_at');
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservations::route('/'),
            'create' => Pages\CreateReservation::route('/create'),
            'edit' => Pages\EditReservation::route('/{record}/edit'),
        ];
    }
}
