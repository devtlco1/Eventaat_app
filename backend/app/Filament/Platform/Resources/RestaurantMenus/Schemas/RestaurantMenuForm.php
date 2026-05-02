<?php

namespace App\Filament\Platform\Resources\RestaurantMenus\Schemas;

use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuResource;
use App\Livewire\Filament\RestaurantMenuStructuredContent;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantMenu;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RestaurantMenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basics')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->options(fn () => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('branch_id', null)),

                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(function (Get $get) {
                                $restaurantId = $get('restaurant_id');
                                if (! $restaurantId) {
                                    return [];
                                }

                                return Branch::query()
                                    ->where('restaurant_id', $restaurantId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->nullable()
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, $fail) use ($get): void {
                                    if (! $value) {
                                        return;
                                    }

                                    $restaurantId = $get('restaurant_id');
                                    if (! $restaurantId) {
                                        $fail('Select a restaurant first.');

                                        return;
                                    }

                                    $ok = Branch::query()
                                        ->whereKey($value)
                                        ->where('restaurant_id', $restaurantId)
                                        ->exists();

                                    if (! $ok) {
                                        $fail('The selected branch does not belong to the selected restaurant.');
                                    }
                                },
                            ]),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                $slug = $get('slug');
                                if (filled($slug)) {
                                    return;
                                }

                                if (blank($state)) {
                                    return;
                                }

                                $set('slug', Str::slug($state));
                            }),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->unique(table: RestaurantMenu::class, ignoreRecord: true),
                        Select::make('status')
                            ->required()
                            ->options(array_combine(RestaurantMenu::STATUSES, RestaurantMenu::STATUSES))
                            ->default(RestaurantMenu::STATUS_DRAFT),
                        Select::make('menu_mode')
                            ->label('Menu mode')
                            ->required()
                            ->options([
                                RestaurantMenu::MODE_STRUCTURED => 'Structured (categories & items)',
                                RestaurantMenu::MODE_PDF_UPLOAD => 'PDF upload',
                                RestaurantMenu::MODE_EXTERNAL_LINK => 'External link',
                            ])
                            ->default(RestaurantMenu::MODE_STRUCTURED)
                            ->live(),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('display_order')
                            ->label('Display order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ]),
                ])
                ->columnSpanFull(),

            Section::make('Structured menu')
                ->compact()
                ->description('Save the menu first, then add categories and items from the edit screen.')
                ->visible(fn (Get $get, $livewire): bool => $get('menu_mode') === RestaurantMenu::MODE_STRUCTURED
                    && $livewire instanceof CreateRecord)
                ->columnSpanFull(),

            Section::make()
                ->compact()
                ->visible(fn (Get $get, $livewire): bool => $get('menu_mode') === RestaurantMenu::MODE_STRUCTURED
                    && $livewire instanceof EditRecord
                    && RestaurantMenuResource::canView($livewire->getRecord()))
                ->schema([
                    Livewire::make(RestaurantMenuStructuredContent::class)
                        ->key(fn ($livewire): string => $livewire instanceof EditRecord
                            ? 'restaurant-menu-structured-'.$livewire->getRecord()->getKey()
                            : 'restaurant-menu-structured'),
                ])
                ->columnSpanFull(),

            Section::make('PDF menu')
                ->compact()
                ->visible(fn (Get $get): bool => $get('menu_mode') === RestaurantMenu::MODE_PDF_UPLOAD)
                ->schema([
                    FileUpload::make('menu_file_path')
                        ->label('Menu PDF')
                        ->disk('public')
                        ->directory('menus')
                        ->visibility('public')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxFiles(1)
                        ->downloadable()
                        ->openable()
                        ->required(fn (Get $get): bool => $get('menu_mode') === RestaurantMenu::MODE_PDF_UPLOAD),
                ]),

            Section::make('External menu')
                ->compact()
                ->visible(fn (Get $get): bool => $get('menu_mode') === RestaurantMenu::MODE_EXTERNAL_LINK)
                ->schema([
                    TextInput::make('menu_url')
                        ->label('Menu URL')
                        ->url()
                        ->maxLength(2048)
                        ->nullable()
                        ->required(fn (Get $get): bool => $get('menu_mode') === RestaurantMenu::MODE_EXTERNAL_LINK),
                ]),

            Section::make('Description')
                ->collapsed()
                ->schema([
                    Textarea::make('description')->rows(4)->columnSpanFull()->nullable(),
                ]),

            Section::make('Notes')
                ->collapsed()
                ->schema([
                    Textarea::make('notes')->rows(3)->columnSpanFull()->nullable(),
                ]),
        ]);
    }
}
