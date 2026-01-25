<?php

namespace App\Filament\Resources\EmployeeScheduleAssignmentResource\Pages;

use App\Filament\Resources\EmployeeScheduleAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeScheduleAssignment extends EditRecord
{
    protected static string $resource = EmployeeScheduleAssignmentResource::class;

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
