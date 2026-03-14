<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Tabs::make('Translations')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('SR (source)')
                        ->schema([
                            TextInput::make('title_i18n.sr')
                                ->label('Title (SR)')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, callable $set) =>
                                    $set('slug', Str::slug((string) $state))
                                ),

                            RichEditor::make('content_i18n.sr')
                                ->label('Content (SR)')
                                ->required()
                                ->columnSpanFull(),
                        ]),

                    Tab::make('EN')
                        ->schema([
                            TextInput::make('title_i18n.en')
                                ->label('Title (EN)')
                                ->helperText('Optional. If empty, SR content is used.'),

                            RichEditor::make('content_i18n.en')
                                ->label('Content (EN)')
                                ->helperText('Optional. If empty, SR content is used.')
                                ->columnSpanFull(),
                        ]),

                    Tab::make('RU')
                        ->schema([
                            TextInput::make('title_i18n.ru')
                                ->label('Title (RU)')
                                ->helperText('Optional. If empty, SR content is used.'),

                            RichEditor::make('content_i18n.ru')
                                ->label('Content (RU)')
                                ->helperText('Optional. If empty, SR content is used.')
                                ->columnSpanFull(),
                        ]),
                ]),

            TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true),

            TextInput::make('priority')
                ->required()
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('slug'),
                IconColumn::make('has_en_translation')
                    ->label('EN')
                    ->boolean()
                    ->getStateUsing(fn (Page $record): bool =>
                        filled(data_get($record->title_i18n, 'en'))
                        && filled(data_get($record->content_i18n, 'en'))
                    ),
                IconColumn::make('has_ru_translation')
                    ->label('RU')
                    ->boolean()
                    ->getStateUsing(fn (Page $record): bool =>
                        filled(data_get($record->title_i18n, 'ru'))
                        && filled(data_get($record->content_i18n, 'ru'))
                    ),
                TextColumn::make('priority')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                //
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
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
