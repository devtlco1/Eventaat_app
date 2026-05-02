<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Filament\Facades\Filament;

trait AuthorizesSuperAdmin
{
    protected static function authIsSuperAdmin(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasRole('super_admin');
    }
}
