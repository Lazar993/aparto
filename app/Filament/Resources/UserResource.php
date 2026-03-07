<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\{Form, Components\Grid, Components\Section, Components\Select, Components\TextInput};
use Filament\Resources\Resource;
use Filament\Tables\{Table, Actions, Columns\TextColumn};
use Illuminate\Support\Facades\Hash;

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
                        Select::make('roles')
                            ->label('Role')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Select one or more roles for this user'),

                        Select::make('user_type')
                            ->label('User Type')
                            ->options(User::userTypeOptions())
                            ->required()
                            ->native(false)
                            ->default(User::TYPE_FRONT)
                            ->helperText('Defines primary app role: admin, host, or front user.'),
                    ])
                    ->collapsible(),
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
                    ->icon('heroicon-o-envelope'),
                    
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('user_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => User::userTypeOptions()[$state] ?? 'Unknown')
                    ->color(fn (?string $state): string => match ($state) {
                        User::TYPE_ADMIN => 'danger',
                        User::TYPE_HOST => 'warning',
                        User::TYPE_FRONT => 'info',
                        default => 'gray',
                    })
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
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
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

}
