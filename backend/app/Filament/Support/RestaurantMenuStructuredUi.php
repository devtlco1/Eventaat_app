<?php

namespace App\Filament\Support;

/**
 * Structured menus use nested repeaters on the menu Create/Edit form.
 * Set to true only if you need the Categories relation manager tab as a fallback.
 */
final class RestaurantMenuStructuredUi
{
    public const SHOW_CATEGORIES_RELATION_MANAGER_FALLBACK = false;
}
