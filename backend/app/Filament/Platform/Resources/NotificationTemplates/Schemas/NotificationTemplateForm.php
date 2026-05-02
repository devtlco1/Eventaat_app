<?php

namespace App\Filament\Platform\Resources\NotificationTemplates\Schemas;

use App\Models\BookingNotification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NotificationTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('event')
                            ->required()
                            ->options(array_combine(BookingNotification::EVENTS, BookingNotification::EVENTS))
                            ->searchable(),
                        Toggle::make('is_active')
                            ->default(true),
                        TextInput::make('channel')
                            ->required()
                            ->default('internal')
                            ->disabled()
                            ->dehydrated(),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('locale')
                            ->required()
                            ->default('en')
                            ->disabled()
                            ->dehydrated(),
                    ]),
                    TextInput::make('title_template')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('body_template')
                        ->required()
                        ->rows(6)
                        ->columnSpanFull(),
                ]),

            Section::make('Notes')
                ->compact()
                ->collapsed()
                ->schema([
                    Textarea::make('notes')
                        ->nullable()
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
