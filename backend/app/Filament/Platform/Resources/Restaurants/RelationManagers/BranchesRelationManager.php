<?php

namespace App\Filament\Platform\Resources\Restaurants\RelationManagers;

use App\Enums\BranchStatus;
use App\Filament\Support\FilamentSchemaLayout;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    public function form(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components([
            Section::make()
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('code')->required()->maxLength(64),
                        Select::make('status')
                            ->required()
                            ->options(array_combine(
                                array_map(fn (BranchStatus $s) => $s->value, BranchStatus::cases()),
                                array_map(fn (BranchStatus $s) => $s->value, BranchStatus::cases()),
                            ))
                            ->default(BranchStatus::Active->value),
                    ]),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->searchable(),
                TextColumn::make('status')->badge(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
