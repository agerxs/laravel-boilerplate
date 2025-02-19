<?php

namespace App\Filament\Resources\SubPrefectResource\Pages;

use App\Filament\Resources\SubPrefectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubPrefects extends ListRecords
{
    protected static string $resource = SubPrefectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 