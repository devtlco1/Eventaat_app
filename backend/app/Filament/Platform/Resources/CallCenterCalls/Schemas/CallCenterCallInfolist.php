<?php

namespace App\Filament\Platform\Resources\CallCenterCalls\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CallCenterCallInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Call')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('created_at')->dateTime()->placeholder('—'),
                        TextEntry::make('direction')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state?->label())
                            ->color(fn ($record) => $record->direction->filamentColor()),
                        TextEntry::make('reason')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state?->label())
                            ->color(fn ($record) => $record->reason->filamentColor()),
                        TextEntry::make('outcome')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state?->label())
                            ->color(fn ($record) => $record->outcome->filamentColor()),
                        TextEntry::make('restaurant.name')->label('Restaurant')->placeholder('—'),
                        TextEntry::make('booking_id')
                            ->label('Booking')
                            ->formatStateUsing(fn (?int $state): string => $state ? '#'.$state : '—'),
                        TextEntry::make('support_ticket_id')
                            ->label('Support ticket')
                            ->formatStateUsing(function (?int $state, $record): string {
                                if (! $state) {
                                    return '—';
                                }

                                $subject = $record->supportTicket?->subject;

                                return $subject ? '#'.$state.' · '.$subject : '#'.$state;
                            }),
                        TextEntry::make('customerUser.name')->label('Customer')->placeholder('—'),
                        TextEntry::make('phone')->placeholder('—'),
                        TextEntry::make('caller_name')->placeholder('—'),
                        TextEntry::make('handledByUser.name')->label('Handled by')->placeholder('—'),
                        TextEntry::make('follow_up_at')->dateTime()->placeholder('—'),
                        TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('updated_at')->since()->placeholder('—'),
                    ]),
                ]),
            Section::make('Notes')
                ->collapsed()
                ->schema([
                    TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                ]),
            Section::make('Metadata')
                ->collapsed()
                ->schema([
                    TextEntry::make('metadata')
                        ->placeholder('—')
                        ->columnSpanFull()
                        ->formatStateUsing(function ($state): ?string {
                            if ($state === null || $state === []) {
                                return null;
                            }

                            $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                            return $json !== false ? $json : null;
                        }),
                ]),
        ]);
    }
}
