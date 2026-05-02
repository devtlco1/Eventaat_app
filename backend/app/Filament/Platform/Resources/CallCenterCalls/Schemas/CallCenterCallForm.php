<?php

namespace App\Filament\Platform\Resources\CallCenterCalls\Schemas;

use App\Enums\CallCenterCallDirection;
use App\Enums\CallCenterCallOutcome;
use App\Enums\CallCenterCallReason;
use App\Models\Booking;
use App\Models\SupportTicket;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CallCenterCallForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Call')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('direction')
                            ->label('Direction')
                            ->options(collect(CallCenterCallDirection::cases())->mapWithKeys(
                                fn (CallCenterCallDirection $c): array => [$c->value => $c->label()]
                            )->all())
                            ->required()
                            ->native(false),
                        Select::make('reason')
                            ->label('Reason')
                            ->options(collect(CallCenterCallReason::cases())->mapWithKeys(
                                fn (CallCenterCallReason $c): array => [$c->value => $c->label()]
                            )->all())
                            ->required()
                            ->native(false),
                        Select::make('outcome')
                            ->label('Outcome')
                            ->options(collect(CallCenterCallOutcome::cases())->mapWithKeys(
                                fn (CallCenterCallOutcome $c): array => [$c->value => $c->label()]
                            )->all())
                            ->required()
                            ->native(false),
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->relationship('restaurant', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('booking_id', null);
                                $set('support_ticket_id', null);
                            }),
                        Select::make('booking_id')
                            ->label('Booking')
                            ->options(function (Get $get): array {
                                $rid = $get('restaurant_id');
                                if (blank($rid)) {
                                    return [];
                                }

                                return Booking::query()
                                    ->where('restaurant_id', (int) $rid)
                                    ->orderByDesc('id')
                                    ->limit(150)
                                    ->get()
                                    ->mapWithKeys(function (Booking $b): array {
                                        $line = '#'.$b->id.' · '.$b->starts_at->format('Y-m-d H:i').' · '.$b->status->value;

                                        return [$b->id => $line];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->nullable(),
                        Select::make('support_ticket_id')
                            ->label('Support ticket')
                            ->options(function (Get $get): array {
                                $rid = $get('restaurant_id');
                                $query = SupportTicket::query()->orderByDesc('id')->limit(200);

                                if (filled($rid)) {
                                    $query->where(function ($q) use ($rid): void {
                                        $q->whereNull('restaurant_id')
                                            ->orWhere('restaurant_id', (int) $rid);
                                    });
                                }

                                return $query->get()->mapWithKeys(function (SupportTicket $t): array {
                                    return [$t->id => '#'.$t->id.' · '.$t->subject];
                                })->all();
                            })
                            ->searchable()
                            ->nullable(),
                        Select::make('customer_user_id')
                            ->label('Customer')
                            ->relationship(
                                name: 'customerUser',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->role('customer')->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(191)
                            ->nullable(),
                        TextInput::make('caller_name')
                            ->maxLength(255)
                            ->nullable(),
                        Select::make('handled_by_user_id')
                            ->label('Handled by')
                            ->relationship(
                                name: 'handledByUser',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereHas(
                                    'roles',
                                    fn ($q) => $q->whereIn('name', ['super_admin', 'operations_admin'])
                                )->orderBy('name'),
                            )
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        DateTimePicker::make('follow_up_at')->nullable()->seconds(false),
                        DateTimePicker::make('completed_at')->nullable()->seconds(false),
                    ]),
                    Textarea::make('notes')
                        ->rows(4)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
