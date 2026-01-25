<?php

namespace App\Filament\Resources\SchedulePatternResource\Pages;

use App\Filament\Resources\SchedulePatternResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSchedulePattern extends CreateRecord
{
    protected static string $resource = SchedulePatternResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
