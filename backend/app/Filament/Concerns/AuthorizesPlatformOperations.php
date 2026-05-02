<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Filament\Facades\Filament;

trait AuthorizesPlatformOperations
{
    protected static function isPlatformUser(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isPlatformOperator();
    }
}
