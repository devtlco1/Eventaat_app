<?php

namespace App\Filament\Platform\Resources\Users\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profile')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('phone')
                        ->tel()
                        ->maxLength(255)
                        ->nullable(),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->nullable()
                        ->minLength(8)
                        ->visible(fn ($livewire): bool => $livewire instanceof CreateRecord || static::editorMaySetPassword())
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                    Select::make('roles')
                        ->label('Roles')
                        ->relationship(
                            name: 'roles',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->where('guard_name', 'web')->orderBy('name'),
                        )
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->visible(fn (): bool => static::editorMayAssignRoles())
                        ->dehydrated(fn (): bool => static::editorMayAssignRoles()),
                ]),
        ]);
    }

    protected static function editorMayAssignRoles(): bool
    {
        return Filament::auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected static function editorMaySetPassword(): bool
    {
        return Filament::auth()->user()?->hasRole('super_admin') ?? false;
    }
}
