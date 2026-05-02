<?php

namespace App\Filament\Platform\Resources\Users\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable()->copyable(),
                TextColumn::make('phone')->searchable()->toggleable()->placeholder('—'),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(', ')
                    ->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(fn (): array => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name', 'name')->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $role = $data['value'] ?? null;

                        if (blank($role)) {
                            return $query;
                        }

                        return $query->whereHas(
                            'roles',
                            fn (Builder $q) => $q->where('name', $role)->where('guard_name', 'web'),
                        );
                    }),
                Filter::make('segment')
                    ->schema([
                        Select::make('segment')
                            ->label('Audience')
                            ->options([
                                'customer' => 'Customer / mobile',
                                'platform' => 'Platform operators',
                                'restaurant_staff' => 'Restaurant staff',
                            ])
                            ->nullable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['segment'] ?? null) {
                            'customer' => $query->whereHas(
                                'roles',
                                fn (Builder $q) => $q->where('name', 'customer')->where('guard_name', 'web'),
                            ),
                            'platform' => $query->whereHas(
                                'roles',
                                fn (Builder $q) => $q->whereIn('name', ['super_admin', 'operations_admin'])
                                    ->where('guard_name', 'web'),
                            ),
                            'restaurant_staff' => $query->whereHas(
                                'roles',
                                fn (Builder $q) => $q->whereIn('name', ['restaurant_owner', 'branch_manager', 'restaurant_host'])
                                    ->where('guard_name', 'web'),
                            ),
                            default => $query,
                        };
                    }),
                Filter::make('created_between')
                    ->schema([
                        DatePicker::make('from')->label('Created from'),
                        DatePicker::make('until')->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, string $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn (Builder $q, string $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([]);
    }
}
