<?php

namespace App\Filament\Resources\LocalCommitteeResource\Pages;

use App\Filament\Resources\LocalCommitteeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLocalCommittees extends ListRecords
{
    protected static string $resource = LocalCommitteeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 