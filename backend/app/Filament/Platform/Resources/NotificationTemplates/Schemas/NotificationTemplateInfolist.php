<?php

namespace App\Filament\Platform\Resources\NotificationTemplates\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class NotificationTemplateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('event')->badge(),
            TextEntry::make('is_active')->label('Active')->badge(),
            TextEntry::make('channel'),
            TextEntry::make('locale'),
            TextEntry::make('title_template')->label('Title template'),
            TextEntry::make('body_template')->label('Body template')->markdown(),
            TextEntry::make('notes')->markdown()->columnSpanFull(),
        ]);
    }
}

