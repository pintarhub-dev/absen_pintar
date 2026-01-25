<?php

namespace App\Filament\Resources\ScheduleOverrideResource\Pages;

use App\Filament\Resources\ScheduleOverrideResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateScheduleOverride extends CreateRecord
{
    protected static string $resource = ScheduleOverrideResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
