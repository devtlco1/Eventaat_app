<?php

namespace App\Filament\Restaurant\Resources\Bookings\Schemas;

use App\Models\RestaurantEvent;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('status')->disabled(),
                DateTimePicker::make('starts_at')->disabled(),
                TextInput::make('party_size')->numeric()->disabled(),

                Select::make('restaurant_event_id')
                    ->label('Event (optional)')
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->helperText(function (Get $get): ?string {
                        $eventId = $get('restaurant_event_id');
                        if (! $eventId) {
                            return 'Optional: link this booking to a published event night.';
                        }

                        /** @var RestaurantEvent|null $event */
                        $event = RestaurantEvent::query()->find((int) $eventId);
                        if (! $event) {
                            return null;
                        }

                        $starts = $event->starts_at?->format('Y-m-d H:i');
                        $ends = $event->ends_at?->format('Y-m-d H:i');

                        return $ends
                            ? "Event time: {$starts} → {$ends}"
                            : ($starts ? "Event time: {$starts}" : null);
                    })
                    ->options(function ($record) {
                        if (! $record) {
                            return [];
                        }

                        $restaurantId = (int) $record->restaurant_id;
                        $branchId = (int) $record->branch_id;

                        return RestaurantEvent::query()
                            ->where('restaurant_id', $restaurantId)
                            ->where('status', RestaurantEvent::STATUS_PUBLISHED)
                            ->whereIn('booking_mode', [RestaurantEvent::BOOKING_MODE_NORMAL, RestaurantEvent::BOOKING_MODE_EVENT])
                            ->where(function ($q) use ($branchId) {
                                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
                            })
                            ->orderByDesc('starts_at')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function (RestaurantEvent $e) {
                                $when = $e->starts_at?->format('Y-m-d H:i') ?? '';
                                return [$e->id => "{$e->title} — {$when}"];
                            })
                            ->all();
                    }),

                Textarea::make('customer_note')->disabled()->columnSpanFull(),
                Textarea::make('restaurant_note')->columnSpanFull(),
            ]);
    }
}
