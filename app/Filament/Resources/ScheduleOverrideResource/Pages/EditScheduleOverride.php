<?php

namespace App\Filament\Resources\ScheduleOverrideResource\Pages;

use App\Filament\Resources\ScheduleOverrideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScheduleOverride extends EditRecord
{
    protected static string $resource = ScheduleOverrideResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
