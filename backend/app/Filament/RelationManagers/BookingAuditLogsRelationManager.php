<?php

namespace App\Filament\RelationManagers;

use App\Filament\Support\FilamentSchemaLayout;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingAuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    protected static ?string $title = 'Audit trail';

    public function form(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('action')->badge()->sortable(),
                TextColumn::make('from_status')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('to_status')->badge()->sortable(),
                TextColumn::make('actor.name')->label('Actor')->placeholder('—'),
                TextColumn::make('actor.email')->label('Actor email')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('message')->limit(48)->toggleable(isToggledHiddenByDefault: true)->placeholder('—'),
                TextColumn::make('metadata')
                    ->formatStateUsing(function ($state): string {
                        return is_array($state) && $state !== [] ? json_encode($state) : '—';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->headerActions([])
            ->recordActions([])
            ->bulkActions([]);
    }
}
