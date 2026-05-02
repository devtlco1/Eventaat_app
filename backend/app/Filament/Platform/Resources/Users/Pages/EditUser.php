<?php

namespace App\Filament\Platform\Resources\Users\Pages;

use App\Filament\Platform\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => UserResource::canDelete($this->getRecord())),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! isset($data['password']) || $data['password'] === '' || $data['password'] === null) {
            unset($data['password']);
        }

        return $data;
    }
}
