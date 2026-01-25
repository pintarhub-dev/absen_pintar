<?php

namespace App\Filament\Resources\EmployeeScheduleAssignmentResource\Pages;

use App\Filament\Resources\EmployeeScheduleAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeScheduleAssignment extends CreateRecord
{
    protected static string $resource = EmployeeScheduleAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
