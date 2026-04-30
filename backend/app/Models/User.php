<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;

    use HasFactory, Notifiable;
    use HasRoles;

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'platform' => $this->hasAnyRole(['super_admin', 'operations_admin']),
            'restaurant' => $this->hasAnyRole(['restaurant_owner', 'branch_manager', 'restaurant_host']),
            default => false,
        };
    }

    public function restaurantStaffAssignments(): HasMany
    {
        return $this->hasMany(RestaurantStaffAssignment::class);
    }

    /**
     * @return array<int, int>
     */
    public function scopedRestaurantIds(): array
    {
        if (! $this->hasAnyRole(['restaurant_owner', 'branch_manager', 'restaurant_host'])) {
            return [];
        }

        return collect($this->restaurantStaffAssignments()
            ->select('restaurant_id')
            ->distinct()
            ->pluck('restaurant_id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function scopedBranchIds(): array
    {
        if (! $this->hasAnyRole(['branch_manager', 'restaurant_host'])) {
            return [];
        }

        return collect($this->restaurantStaffAssignments()
            ->whereNotNull('branch_id')
            ->select('branch_id')
            ->distinct()
            ->pluck('branch_id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
