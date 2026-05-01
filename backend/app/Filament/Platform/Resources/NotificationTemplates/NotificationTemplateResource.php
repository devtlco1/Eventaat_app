<?php

namespace App\Filament\Platform\Resources\NotificationTemplates;

use App\Filament\Platform\Resources\NotificationTemplates\Pages\CreateNotificationTemplate;
use App\Filament\Platform\Resources\NotificationTemplates\Pages\EditNotificationTemplate;
use App\Filament\Platform\Resources\NotificationTemplates\Pages\ListNotificationTemplates;
use App\Filament\Platform\Resources\NotificationTemplates\Pages\ViewNotificationTemplate;
use App\Filament\Platform\Resources\NotificationTemplates\Schemas\NotificationTemplateForm;
use App\Filament\Platform\Resources\NotificationTemplates\Schemas\NotificationTemplateInfolist;
use App\Filament\Platform\Resources\NotificationTemplates\Tables\NotificationTemplatesTable;
use App\Models\NotificationTemplate;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 16;

    protected static ?string $navigationLabel = 'Notification templates';

    protected static ?string $modelLabel = 'notification template';

    protected static ?string $pluralModelLabel = 'notification templates';

    private static function isPlatformUser(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->hasAnyRole(['super_admin', 'operations_admin']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::isPlatformUser();
    }

    public static function canViewAny(): bool
    {
        return self::isPlatformUser();
    }

    public static function canCreate(): bool
    {
        return self::isPlatformUser();
    }

    public static function canEdit($record): bool
    {
        return self::isPlatformUser();
    }

    public static function canDelete($record): bool
    {
        return self::isPlatformUser();
    }

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

