<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Resources\LeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\LeaveRequest;
use Filament\Notifications\Notification;

class EditLeaveRequest extends EditRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeFill(): void
    {
        if ($this->record->status !== 'pending') {

            Notification::make()
                ->title('Akses Ditolak')
                ->body('Pengajuan yang sudah diproses (Approved/Rejected) tidak dapat diedit lagi.')
                ->warning()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn(LeaveRequest $record) => $record->status === 'pending'),
        ];
    }
}
