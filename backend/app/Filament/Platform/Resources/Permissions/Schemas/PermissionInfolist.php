<?php

namespace App\Filament\Platform\Resources\Permissions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PermissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Permission')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('name')->columnSpanFull(),
                        TextEntry::make('guard_name'),
                        TextEntry::make('roles_count')
                            ->label('Roles using this permission')
                            ->numeric()
                            ->state(fn ($record): int => $record->roles()->count()),
                    ]),
                    TextEntry::make('roles_list')
                        ->label('Roles')
                        ->state(fn ($record): string => $record->roles()->orderBy('name')->pluck('name')->implode(', ') ?: '—')
                        ->columnSpanFull(),
                ]),
            Section::make('System')
                ->compact()
                ->collapsed()
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                    ]),
                ]),
        ]);
    }
}
