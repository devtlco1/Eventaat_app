<?php

namespace App\Filament\Platform\Resources\NotificationTemplates;

use App\Filament\Concerns\GrantsPlatformOperationsCrud;
use App\Filament\Platform\Resources\NotificationTemplates\Pages\CreateNotificationTemplate;
use App\Filament\Platform\Resources\NotificationTemplates\Pages\EditNotificationTemplate;
use App\Filament\Platform\Resources\NotificationTemplates\Pages\ListNotificationTemplates;
use App\Filament\Platform\Resources\NotificationTemplates\Pages\ViewNotificationTemplate;
use App\Filament\Platform\Resources\NotificationTemplates\Schemas\NotificationTemplateForm;
use App\Filament\Platform\Resources\NotificationTemplates\Schemas\NotificationTemplateInfolist;
use App\Filament\Platform\Resources\NotificationTemplates\Tables\NotificationTemplatesTable;
use App\Models\NotificationTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NotificationTemplateResource extends Resource
{
    use GrantsPlatformOperationsCrud;

    protected static ?string $model = NotificationTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 16;

    protected static ?string $navigationLabel = 'Notification templates';

    protected static ?string $modelLabel = 'notification template';

    protected static ?string $pluralModelLabel = 'notification templates';

    public static function form(Schema $schema): Schema
    {
        return NotificationTemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NotificationTemplateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotificationTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationTemplates::route('/'),
            'create' => CreateNotificationTemplate::route('/create'),
            'view' => ViewNotificationTemplate::route('/{record}'),
            'edit' => EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
