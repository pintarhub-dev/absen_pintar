<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Resources\LeaveRequestResource;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Actions;

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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // DATA LAMA (Sebelum Edit)
            $oldLeaveType = $record->leaveType;
            $oldYear = Carbon::parse($record->start_date)->year;
            $oldDuration = $record->duration_days;

            // DATA BARU (Dari Form)
            $newLeaveType = LeaveType::find($data['leave_type_id']);
            $newStart = Carbon::parse($data['start_date']);
            $newYear = $newStart->year;
            // Hitung ulang durasi dari data baru (untuk memastikan akurasi)
            $newEnd = Carbon::parse($data['end_date']);
            $newDuration = $newStart->diffInDays($newEnd) + 1;

            // Pastikan data duration di-overwrite hasil hitungan (biar gak dimanipulasi client)
            $data['duration_days'] = $newDuration;


            // 1. REFUND SALDO LAMA (Kembalikan 'taken')
            if ($oldLeaveType->deducts_quota) {
                $oldBalance = LeaveBalance::where('employee_id', $record->employee_id)
                    ->where('leave_type_id', $oldLeaveType->id)
                    ->where('year', $oldYear)
                    ->lockForUpdate()
                    ->first();

                if ($oldBalance) {
                    $oldBalance->decrement('taken', $oldDuration);
                }
            }

            // 2. POTONG SALDO BARU (Tambah 'taken')
            if ($newLeaveType->deducts_quota) {
                $newBalance = LeaveBalance::where('employee_id', $data['employee_id'])
                    ->where('leave_type_id', $newLeaveType->id)
                    ->where('year', $newYear)
                    ->lockForUpdate()
                    ->first();

                if (!$newBalance || $newBalance->remaining < $newDuration) {
                    Notification::make()->title('Gagal')->body('Saldo tidak cukup untuk perubahan ini.')->danger()->send();
                    throw new \Exception('Saldo Kurang');
                }

                $newBalance->increment('taken', $newDuration);
            }

            // 3. Update Record
            $record->update($data);

            return $record;
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn(LeaveRequest $record) => $record->status === 'pending'),
        ];
    }
}
