<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Pages;
use App\Models\User;
use Filament\Forms\{Form, Components};
use Filament\Resources\Resource;
use Filament\Tables\{Table, Actions, Columns};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProfileResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Profiles';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Profile';

    protected static ?string $pluralModelLabel = 'Profiles';

    protected static ?string $slug = 'profiles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make('Profile Information')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255),

                                Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                            ]),
                    ]),

                Components\Section::make('Profile Image')
                    ->schema([
                        Components\FileUpload::make('profile_image')
                            ->label('Profile Image')
                            ->image()
                            ->directory('profile-images')
                            ->disk('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('400')
                            ->imageResizeTargetHeight('400')
                            ->maxSize(2048)
                            ->helperText('Upload a profile photo (max 2MB). Will be cropped to square.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\ImageColumn::make('profile_image')
                    ->label('Photo')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(asset('images/default-profile-image.png')),

                Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfiles::route('/'),
            'edit' => Pages\EditProfile::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Hosts can only see their own profile
        if ($user && ($user->isHost() || $user->hasRole('host'))) {
            $query->where('id', $user->id);
        }

        // Only show host and admin users (not front users)
        $query->whereIn('user_type', [User::TYPE_HOST, User::TYPE_ADMIN]);

        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * Skip the default model policy so this resource doesn't conflict
     * with the UserPolicy already registered for the User model.
     * Access is controlled by Filament Shield permissions (view_profile, update_profile, etc.)
     * and by the scoped getEloquentQuery above.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasPermissionTo('view_any_profile')
            || $user->hasPermissionTo('view_profile');
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Hosts can only edit their own profile
        if (($user->isHost() || $user->hasRole('host')) && (int) $record->id !== (int) $user->id) {
            return false;
        }

        return $user->hasPermissionTo('update_profile');
    }
}
