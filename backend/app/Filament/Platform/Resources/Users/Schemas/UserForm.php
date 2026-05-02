<?php

namespace App\Filament\Platform\Resources\Users\Schemas;

use App\Filament\Support\FilamentSchemaLayout;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components([
            Section::make('Profile')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
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
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->minLength(8)
                            ->visible(fn ($livewire): bool => $livewire instanceof CreateRecord || static::editorMaySetPassword())
                            ->helperText(fn ($livewire): ?string => $livewire instanceof CreateRecord
                                ? 'Leave blank to auto-generate. Minimum 8 characters if set.'
                                : (Filament::auth()->user()?->hasRole('super_admin') ? 'Leave blank to keep current.' : null))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->columnSpan(fn (): int => static::editorMayAssignRoles() ? 2 : 3),
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
