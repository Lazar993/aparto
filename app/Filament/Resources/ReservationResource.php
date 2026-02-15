<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservationResource\Pages;
use App\Models\Reservation;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 2;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('apartment_id')
                    ->relationship('apartment', 'title')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(120),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(160),
                Forms\Components\TextInput::make('phone')
                    ->required()
                    ->maxLength(40),
                Forms\Components\DatePicker::make('date_from')
                    ->required(),
                Forms\Components\DatePicker::make('date_to')
                    ->required(),
                Forms\Components\TextInput::make('nights')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('price_per_night')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('total_price')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('deposit_amount')
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'canceled' => 'Canceled',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('payment_provider')
                    ->maxLength(120),
                Forms\Components\TextInput::make('payment_reference')
                    ->maxLength(160),
                Forms\Components\DateTimePicker::make('paid_at'),
                Forms\Components\Textarea::make('note')
                    ->rows(3)
                    ->maxLength(1000),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('apartment.title')
                    ->label('Apartment')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_from')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_to')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nights')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->formatStateUsing(function ($state): string {
                        return sprintf('%s %0.2f', config('website.currency'), (float) $state);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('deposit_amount')
                    ->formatStateUsing(function ($state): string {
                        return sprintf('%s %0.2f', config('website.currency'), (float) $state);
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('balance_due')
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'confirmed' => 'success',
                        'canceled' => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('note')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'canceled' => 'Canceled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
