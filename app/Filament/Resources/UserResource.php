<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Resources\Form;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\{TextInput, Toggle, Section, Grid, Card};
use Filament\Tables\Columns\{IconColumn, TextColumn};
use Filament\Tables\Actions\{EditAction, DeleteBulkAction};

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Users';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->schema([
                        Section::make('User Information')
                            ->description('Basic information about the user')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Full Name')
                                            ->placeholder('Enter full name')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(1),

                                        TextInput::make('email')
                                            ->label('Email Address')
                                            ->placeholder('user@example.com')
                                            ->email()
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->collapsible(),

                        Section::make('Security')
                            ->description('Password and account security settings')
                            ->schema([
                                TextInput::make('password')
                                    ->label('Password')
                                    ->placeholder('Enter password')
                                    ->password()
                                    ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null)
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context) => $context === 'create')
                                    ->helperText(fn (string $context) => $context === 'edit' ? 'Leave blank to keep current password' : 'Minimum 8 characters recommended')
                                    ->minLength(8)
                                    ->maxLength(255),
                            ])
                            ->collapsible(),

                        Section::make('Permissions')
                            ->description('User role and permissions')
                            ->schema([
                                Toggle::make('is_admin')
                                    ->label('Administrator')
                                    ->helperText('Grant full administrative access to this user')
                                    ->default(false)
                                    ->inline(false),
                            ])
                            ->collapsible(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-mail'),
                    
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->trueIcon('heroicon-o-badge-check')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('admin');
    }

}
