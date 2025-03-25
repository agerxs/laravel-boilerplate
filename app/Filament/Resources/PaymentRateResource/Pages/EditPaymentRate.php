<?php

namespace App\Filament\Resources\PaymentRateResource\Pages;

use App\Filament\Resources\PaymentRateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentRate extends EditRecord
{
    protected static string $resource = PaymentRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Supprimer'),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 