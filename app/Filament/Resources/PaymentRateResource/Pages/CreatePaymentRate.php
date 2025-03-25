<?php

namespace App\Filament\Resources\PaymentRateResource\Pages;

use App\Filament\Resources\PaymentRateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentRate extends CreateRecord
{
    protected static string $resource = PaymentRateResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 