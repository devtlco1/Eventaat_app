<?php

namespace App\Filament\Platform\Resources\Roles\Tables;

use App\Filament\Platform\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('guard_name')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Users')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Role $record): bool => RoleResource::canDelete($record)),
            ])
            ->bulkActions([]);
    }
}
