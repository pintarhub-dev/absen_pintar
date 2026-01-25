<?php

namespace App\Filament\Resources\AttendanceSummaryResource\Pages;

use App\Filament\Resources\AttendanceSummaryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceSummary extends EditRecord
{
    protected static string $resource = AttendanceSummaryResource::class;

    protected function getRedirectUrl(): string
    {
        // Biar habis save balik ke list
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
