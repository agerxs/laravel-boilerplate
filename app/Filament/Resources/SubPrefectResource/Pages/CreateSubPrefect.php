<?php

namespace App\Filament\Resources\SubPrefectResource\Pages;

use App\Filament\Resources\SubPrefectResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateSubPrefect extends CreateRecord
{
    protected static string $resource = SubPrefectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['password'] = bcrypt('password');
        $data['email_verified_at'] = now();
        $data['remember_token'] = Str::random(10);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Assigner le rôle sous-prefet
        $this->record->assignRole('sous-prefet');
    }
} 