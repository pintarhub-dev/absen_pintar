<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Resources\LeaveRequestResource;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $leaveType = LeaveType::find($data['leave_type_id']);

            // 1. Cek & Potong Saldo
            if ($leaveType && $leaveType->deducts_quota) {
                $year = Carbon::parse($data['start_date'])->year;
                $duration = $data['duration_days'];

                $balance = LeaveBalance::where('employee_id', $data['employee_id'])
                    ->where('leave_type_id', $data['leave_type_id'])
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();

                // Validasi
                if (!$balance || $balance->remaining < $duration) {
                    Notification::make()->title('Gagal')->body('Saldo tidak cukup.')->danger()->send();
                    throw new \Exception('Saldo Kurang'); // Hentikan proses
                }
                $balance->increment('taken', $duration);
            }

            $employee = Employee::find($data['employee_id']);
            $data['tenant_id'] = $employee->tenant_id;
            return static::getModel()::create($data);
        });
    }
}
