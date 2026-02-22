<?php

namespace App\Filament\Resources;

use App\Services\OpenAiService;
use App\Filament\Resources\ApartmentResource\Pages;
use App\Models\Apartment;
use Filament\Forms\Form;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\{DatePicker, FileUpload, Hidden, Repeater, Section, TextInput, Textarea, Toggle};
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Actions;
use Filament\Tables\Columns\{IconColumn, ImageColumn, TextColumn};
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ApartmentResource extends Resource
{
    protected static ?string $model = Apartment::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Section::make('Basic Details')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('description')
                        ->columnSpanFull()
                        // ->hint('Generate with AI')
                        ->hintColor('primary')
                        ->hintAction(
                            Action::make('generateDescription')
                                ->label('Generate with AI')
                                ->icon('heroicon-o-sparkles')
                                ->extraAttributes([
                                    'wire:loading.attr' => 'disabled',
                                    'wire:loading.class.delay' => 'opacity-70 cursor-wait',
                                ])
                                ->action(function ($livewire, OpenAiService $openAi): void {
                                    $data = data_get($livewire, 'data', []);
                                    if (empty($data['title']) && empty($data['city'])) {
                                        Notification::make()
                                            ->title('Please enter at least a title or city first.')
                                            ->warning()
                                            ->send();
                                        return;
                                    }
                                    $description = $openAi->generateApartmentDescription([
                                        'title' => $data['title'] ?? null,
                                        'city' => $data['city'] ?? null,
                                        'address' => $data['address'] ?? null,
                                        'rooms' => $data['rooms'] ?? null,
                                        'price_per_night' => $data['price_per_night'] ?? null,
                                        'parking' => $data['parking'] ?? null,
                                        'wifi' => $data['wifi'] ?? null,
                                        'pet_friendly' => $data['pet_friendly'] ?? null,
                                    ]);

                                    if ($description) {
                                        data_set($livewire, 'data.description', $description);
                                    }
                                })
                        ),
                    TextInput::make('city')
                        ->required()
                        ->maxLength(255)
                        ->autocomplete('address-level2'),
                    TextInput::make('address')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->autocomplete('off')
                        ->extraInputAttributes([
                            'data-osm-autocomplete' => 'true',
                            'data-city-input' => 'data.city',
                            'data-lat-input' => 'data.latitude',
                            'data-lng-input' => 'data.longitude',
                        ]),
                        Hidden::make('latitude')
                        ->dehydrated(),
                    Hidden::make('longitude')
                        ->dehydrated(),
                    TextInput::make('rooms')
                        ->numeric()
                        ->default(1),
                ]),
            Section::make('Pricing & Status')
                ->columns(4)
                ->schema([
                    TextInput::make('price_per_night')
                        ->required()
                        ->numeric()
                        ->prefix(config('website.currency'))
                        ->columnSpan(1),
                    TextInput::make('min_nights')
                        ->label('Minimum Nights')
                        ->required()
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->columnSpan(1),
                    Toggle::make('active')
                        ->default(true)
                        ->columnSpan(1)
                        ->inline(false),
                    Toggle::make('parking')
                        ->default(false)
                        ->columnSpan(1)
                        ->inline(false),
                    Toggle::make('wifi')
                        ->label('WiFi')
                        ->default(false)
                        ->columnSpan(1)
                        ->inline(false),
                    Toggle::make('pet_friendly')
                        ->label('Pet Friendly')
                        ->default(false)
                        ->columnSpan(1)
                        ->inline(false),
                ]),
            Section::make('Discounts')
                ->columns(2)
                ->schema([
                    TextInput::make('discount_nights')
                        ->label('Number of Nights for Discount')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Minimum number of nights to qualify for a discount')
                        ->columnSpan(1),
                    TextInput::make('discount_percentage')
                        ->label('Discount Percentage')
                        ->numeric()
                        ->suffix('%')
                        ->minValue(0)
                        ->maxValue(100)
                        ->helperText('Discount percentage for bookings meeting the minimum nights')
                        ->columnSpan(1),
                ]),
            Section::make('Availability & Custom Pricing')
                ->columns(1)
                ->schema([
                    Repeater::make('blocked_dates')
                        ->label('Blocked Date Periods')
                        ->schema([
                            DatePicker::make('from')
                                ->label('From')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                            DatePicker::make('to')
                                ->label('To')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->afterOrEqual('from'),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->helperText('Select date ranges when the apartment is not available for booking')
                        ->defaultItems(0),
                    Repeater::make('custom_pricing')
                        ->label('Custom Pricing Periods')
                        ->schema([
                            DatePicker::make('from')
                                ->label('From')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                            DatePicker::make('to')
                                ->label('To')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->afterOrEqual('from'),
                            TextInput::make('price')
                                ->label('Price per Night')
                                ->required()
                                ->numeric()
                                ->prefix(config('website.currency')),
                        ])
                        ->columns(3)
                        ->collapsible()
                        ->helperText('Set custom prices for date ranges')
                        ->defaultItems(0),
                ]),
            Section::make('Images')
                ->columns(2)
                ->schema([
                    FileUpload::make('lead_image')
                        ->label('Lead Image')
                        ->image()
                        ->directory('apartments/lead_images')
                        ->imageResizeMode('contain')
                        ->imageResizeTargetWidth('600')
                        ->imageResizeTargetHeight('400')
                        ->nullable(),
                    FileUpload::make('gallery_images')
                        ->label('Gallery Images')
                        ->image()
                        ->directory('apartments/gallery_images')
                        ->imageResizeMode('contain')
                        ->imageResizeTargetWidth('600')
                        ->imageResizeTargetHeight('400')
                        ->multiple()
                        ->nullable(),
                ]),
            Hidden::make('user_id')
                ->default(fn () => auth()->id()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                ImageColumn::make('lead_image')->label('Lead Image'),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('active')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('parking')
                    ->boolean()
                    ->sortable(),
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
            'index' => Pages\ListApartments::route('/'),
            'create' => Pages\CreateApartment::route('/create'),
            'edit' => Pages\EditApartment::route('/{record}/edit'),
        ];
    }    

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole('host')) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

}
