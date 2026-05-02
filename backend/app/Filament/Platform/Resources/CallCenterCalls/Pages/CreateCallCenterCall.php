<?php

namespace App\Filament\Platform\Resources\CallCenterCalls\Pages;

use App\Filament\Platform\Resources\CallCenterCalls\CallCenterCallResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCallCenterCall extends CreateRecord
{
    protected static string $resource = CallCenterCallResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['handled_by_user_id'] ?? null)) {
            $data['handled_by_user_id'] = auth()->id();
        }

        return $data;
    }
}
