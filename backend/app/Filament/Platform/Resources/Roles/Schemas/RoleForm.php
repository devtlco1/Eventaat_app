<?php

namespace App\Filament\Platform\Resources\Roles\Schemas;

use App\Support\Platform\CoreRoles;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Role')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->disabled(fn ($livewire): bool => $livewire instanceof EditRecord && CoreRoles::isCore($livewire->getRecord()->name)),
                    TextInput::make('guard_name')
                        ->required()
                        ->default('web')
                        ->maxLength(255)
                        ->disabled(fn ($livewire): bool => $livewire instanceof EditRecord && CoreRoles::isCore($livewire->getRecord()->name)),
                ]),
            Section::make('Permissions')
                ->schema([
                    Select::make('permissions')
                        ->label('Permissions')
                        ->relationship(
                            name: 'permissions',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->where('guard_name', 'web')->orderBy('name'),
                        )
                        ->multiple()
                        ->searchable()
                        ->preload(),
                ]),
        ]);
    }
}
