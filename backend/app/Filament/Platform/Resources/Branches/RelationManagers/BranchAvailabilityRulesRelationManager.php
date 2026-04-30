<?php

namespace App\Filament\Platform\Resources\Branches\RelationManagers;

use App\Filament\Support\BranchAvailabilityRuleFormComponents;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BranchAvailabilityRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'availabilityRule';

    protected static ?string $title = 'Booking availability';

    protected static bool $isLazy = false;

    protected static bool $shouldSkipAuthorization = true;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('branch_id')
                    ->default(fn (): mixed => $this->getOwnerRecord()->getKey())
                    ->required(),
                ...BranchAvailabilityRuleFormComponents::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_booking_enabled')->boolean()->label('Enabled'),
                TextColumn::make('booking_duration_minutes')->label('Duration (min)'),
                TextColumn::make('min_advance_minutes')->label('Min advance'),
                TextColumn::make('max_advance_days')->label('Max advance (days)'),
                TextColumn::make('open_time')->label('Open')->time(),
                TextColumn::make('close_time')->label('Close')->time(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => ! $this->getOwnerRecord()->availabilityRule()->exists()),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
