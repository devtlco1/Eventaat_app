<?php

namespace App\Filament\Concerns;

trait GrantsPlatformOperationsCrud
{
    use AuthorizesPlatformOperations;

    public static function shouldRegisterNavigation(): bool
    {
        return static::isPlatformUser();
    }

    public static function canViewAny(): bool
    {
        return static::isPlatformUser();
    }

    public static function canCreate(): bool
    {
        return static::isPlatformUser();
    }

    public static function canEdit($record): bool
    {
        return static::isPlatformUser();
    }

    public static function canView($record): bool
    {
        return static::isPlatformUser();
    }

    public static function canDelete($record): bool
    {
        return static::isPlatformUser();
    }
}
