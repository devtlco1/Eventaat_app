<?php

namespace App\Filament\Platform\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mobile / customer visibility')
                    ->schema([
                        TextEntry::make('customer_segment')
                            ->label('Customer / mobile account')
                            ->badge()
                            ->state(fn (User $record): string => $record->hasRole('customer')
                                ? 'Yes — mobile customer role'
                                : 'No — not a mobile customer role')
                            ->color(fn (User $record): string => $record->hasRole('customer') ? 'warning' : 'gray'),
                        TextEntry::make('staff_segment')
                            ->label('Restaurant staff')
                            ->badge()
                            ->state(fn (User $record): string => $record->isRestaurantStaff()
                                ? 'Restaurant staff'
                                : 'Not restaurant staff')
                            ->color(fn (User $record): string => $record->isRestaurantStaff() ? 'info' : 'gray'),
                        TextEntry::make('platform_segment')
                            ->label('Platform operator')
                            ->badge()
                            ->state(fn (User $record): string => $record->isPlatformOperator()
                                ? 'Platform operator'
                                : 'Not a platform operator')
                            ->color(fn (User $record): string => $record->isPlatformOperator() ? 'success' : 'gray'),
                    ]),

                Section::make('Identity')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('name'),
                            TextEntry::make('email')->copyable(),
                            TextEntry::make('phone')->placeholder('—'),
                        ]),
                        TextEntry::make('roles_label')
                            ->label('Roles')
                            ->state(fn (User $record): string => $record->roles->pluck('name')->sort()->implode(', ') ?: '—'),
                    ]),

                Section::make('Counts')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('customer_bookings_total')
                                ->label('Bookings as customer')
                                ->numeric()
                                ->state(fn (User $record): int => $record->customerBookings()->count()),
                            TextEntry::make('restaurant_reviews_total')
                                ->label('Reviews as user')
                                ->numeric()
                                ->state(fn (User $record): int => $record->restaurantReviews()->count()),
                        ]),
                    ]),

                Section::make('System')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')->dateTime(),
                            TextEntry::make('updated_at')->dateTime(),
                        ]),
                    ]),
            ]);
    }
}
