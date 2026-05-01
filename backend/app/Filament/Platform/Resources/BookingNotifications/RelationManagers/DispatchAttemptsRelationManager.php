<?php

namespace App\Filament\Platform\Resources\BookingNotifications\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DispatchAttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'dispatchAttempts';

    protected static ?string $title = 'Dispatch attempts';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('attempted_at')->dateTime()->sortable(),
                TextColumn::make('provider')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('provider_message_id')->label('Provider msg id')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('failure_reason')->limit(60)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('attempted_at', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->bulkActions([]);
    }
}

