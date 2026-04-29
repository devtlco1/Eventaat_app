<?php

namespace App\Http\Resources\Mobile;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class MeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = (string) ($this->name ?? '');
        $missing = [];
        if (trim($name) === '') {
            $missing[] = 'name';
        }

        return [
            'id' => $this->id,
            'name' => $name === '' ? null : $name,
            'phone' => $this->phone,
            'role' => 'customer',
            'profile_completed' => count($missing) === 0,
            'missing_fields' => $missing,
        ];
    }
}

