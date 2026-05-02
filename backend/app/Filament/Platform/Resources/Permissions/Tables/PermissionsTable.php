<?php

namespace App\Filament\Platform\Resources\Permissions\Tables;

use App\Filament\Platform\Resources\Permissions\PermissionResource;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class PermissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('guard_name')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('roles_count')
                    ->label('Roles')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
            ])
            ->bulkActions([])
            ->recordUrl(fn (Permission $record): string => PermissionResource::getUrl('view', ['record' => $record]));
    }
}
