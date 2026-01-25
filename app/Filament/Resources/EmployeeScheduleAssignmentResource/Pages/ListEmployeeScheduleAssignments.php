<?php

namespace App\Filament\Resources\EmployeeScheduleAssignmentResource\Pages;

use App\Filament\Resources\EmployeeScheduleAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeScheduleAssignments extends ListRecords
{
    protected static string $resource = EmployeeScheduleAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
