<?php

namespace App\Filament\Restaurant\Resources\RestaurantMenus\Schemas;

use App\Models\Branch;
use App\Models\RestaurantMenu;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RestaurantMenuForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        $restaurantIds = $user?->scopedRestaurantIds() ?? [];
        $branchIds = $user?->scopedBranchIds() ?? [];

        $isBranchScoped = count($branchIds) > 0;

        return $schema->components([
            Section::make('Basics')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->relationship(
                                name: 'restaurant',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereIn('id', $restaurantIds),
                            )
                            ->required()
                            ->searchable()
                            ->default(count($restaurantIds) === 1 ? $restaurantIds[0] : null)
                            ->disabled(count($restaurantIds) === 1)
                            ->dehydrated(true)
                            ->live()
                            ->rule(Rule::in($restaurantIds))
                            ->afterStateUpdated(fn (Set $set) => $set('branch_id', null)),

                        Select::make('branch_id')
                            ->label('Branch'.($isBranchScoped ? '' : ' (optional)'))
                            ->options(function (Get $get) use ($branchIds) {
                                $restaurantId = $get('restaurant_id');
                                if (! $restaurantId) {
                                    return [];
                                }

                                $query = Branch::query()
                                    ->where('restaurant_id', $restaurantId)
                                    ->orderBy('name');

                                if (count($branchIds)) {
                                    $query->whereIn('id', $branchIds);
                                }

                                return $query->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->default($isBranchScoped && count($branchIds) === 1 ? $branchIds[0] : null)
                            ->required($isBranchScoped)
                            ->nullable(! $isBranchScoped)
                            ->hidden($isBranchScoped && count($branchIds) === 1)
                            ->rule(function (Get $get) use ($branchIds) {
                                $restaurantId = $get('restaurant_id');

                                if (! $restaurantId) {
                                    return Rule::prohibitedIf(true);
                                }

                                $rule = Rule::exists(Branch::class, 'id')->where('restaurant_id', $restaurantId);

                                if (count($branchIds)) {
                                    $rule->whereIn('id', $branchIds);
                                }

                                return $rule;
                            }),
                    ]),
                    Grid::make(2)->schema([
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
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->unique(table: RestaurantMenu::class, ignoreRecord: true),
                    ]),
                    Grid::make(2)->schema([
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
                    TextInput::make('display_order')
                        ->label('Display order')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                ]),

            Section::make('PDF menu')
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
