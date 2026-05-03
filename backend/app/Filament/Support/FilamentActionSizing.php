<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Support\Enums\Size;

/**
 * Normalize Filament {@see Action} button sizing so header actions, section triggers,
 * and standard buttons share the same default density while leaving links and icon-only
 * controls on Filament defaults.
 */
final class FilamentActionSizing
{
    public static function configure(): void
    {
        // Apply after concrete actions finish `setUp()` so views/icons resolve safely (Filament runs
        // `configureUsing` closures before `setUp` unless marked important).
        Action::configureUsing(function (Action $action): void {
            if ($action->isIconButton()) {
                return;
            }

            if ($action->isLink()) {
                return;
            }

            $action->defaultSize(Size::Medium);
        }, during: null, isImportant: true);
    }
}
