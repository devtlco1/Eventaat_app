<?php

namespace App\Filament\Platform\Resources\NotificationTemplates\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NotificationTemplateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('event')->badge(),
                        TextEntry::make('is_active')->label('Active')->badge(),
                        TextEntry::make('channel'),
                        TextEntry::make('locale'),
                    ]),
                ]),
            Section::make('Template')
                ->schema([
                    TextEntry::make('title_template')->label('Title template')->columnSpanFull(),
                    TextEntry::make('body_template')->label('Body template')->markdown()->columnSpanFull(),
                ]),
            Section::make('Notes')
                ->schema([
                    TextEntry::make('notes')->markdown()->columnSpanFull()->placeholder('—'),
                ]),
        ]);
    }
}

