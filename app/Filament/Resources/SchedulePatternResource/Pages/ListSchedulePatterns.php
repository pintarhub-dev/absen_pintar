<?php

namespace App\Filament\Resources\SchedulePatternResource\Pages;

use App\Filament\Resources\SchedulePatternResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchedulePatterns extends ListRecords
{
    protected static string $resource = SchedulePatternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
