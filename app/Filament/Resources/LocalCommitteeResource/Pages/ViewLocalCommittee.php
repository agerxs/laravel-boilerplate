<?php

namespace App\Filament\Resources\LocalCommitteeResource\Pages;

use App\Filament\Resources\LocalCommitteeResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewLocalCommittee extends ViewRecord
{
    protected static string $resource = LocalCommitteeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Vous pouvez ajouter des widgets ici si nécessaire
        ];
    }
} 