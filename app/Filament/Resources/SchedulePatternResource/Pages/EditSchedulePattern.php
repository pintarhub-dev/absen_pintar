<?php

namespace App\Filament\Resources\SchedulePatternResource\Pages;

use App\Filament\Resources\SchedulePatternResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchedulePattern extends EditRecord
{
    protected static string $resource = SchedulePatternResource::class;

    protected function getRedirectUrl(): string
    {
        // Biar habis save balik ke list
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
