<?php

namespace App\Observers;

use App\Models\Holiday;
use App\Models\AttendanceSummary;
use App\Models\ScheduleOverride;
use App\Services\ScheduleGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HolidayObserver
{
    /**
     * Dijalankan saat Holiday dibuat atau diedit.
     * Mengubah semua jadwal karyawan di tanggal tersebut menjadi OFF,
     * KECUALI yang punya Override.
     */
    public function saved(Holiday $holiday): void
    {
        $this->applyHoliday($holiday);
    }

    /**
     * Dijalankan saat Holiday dihapus.
     * Mengembalikan jadwal karyawan ke Pattern Asli.
     */
    public function deleted(Holiday $holiday): void
    {
        $this->revertHoliday($holiday);
    }

    private function applyHoliday(Holiday $holiday)
    {
        // 1. Ambil semua summary di tanggal & tenant tersebut
        $summaries = AttendanceSummary::where('date', $holiday->date)
            ->where('tenant_id', $holiday->tenant_id)
            ->get();

        foreach ($summaries as $summary) {
            // 2. CEK PROTEKSI OVERRIDE
            // Jangan ubah jika karyawan punya Override di tanggal itu!
            $hasOverride = ScheduleOverride::where('employee_id', $summary->employee_id)
                ->where('date', $holiday->date)
                ->exists();

            if (!$hasOverride) {
                // 3. Set jadi OFF
                $summary->update([
                    'shift_id' => null,
                    'schedule_in' => null,
                    'schedule_out' => null,
                    'status' => $summary->clock_in ? $summary->status : 'off', // Jangan ubah status kalau udah absen
                ]);
            }
        }
    }

    private function revertHoliday(Holiday $holiday)
    {
        // Kalau libur dihapus, hitung ulang jadwal berdasarkan Pattern
        $summaries = AttendanceSummary::where('date', $holiday->date)
            ->where('tenant_id', $holiday->tenant_id)
            ->get();

        $generator = new ScheduleGenerator();

        foreach ($summaries as $summary) {
            $employee = $summary->employee;

            // Cek Override dulu, kalau ada override, biarkan (jangan di-revert ke pattern)
            $hasOverride = ScheduleOverride::where('employee_id', $employee->id)
                ->where('date', $holiday->date)
                ->exists();

            if ($hasOverride) continue;

            // Hitung Pattern Asli
            $originalShift = $generator->getPatternShift($employee, Carbon::parse($holiday->date));

            $updateData = [];
            if ($originalShift) {
                $updateData['shift_id'] = $originalShift->id;
                if ($originalShift->is_day_off) {
                    $updateData['schedule_in'] = null;
                    $updateData['schedule_out'] = null;
                    if (!$summary->clock_in) $updateData['status'] = 'off';
                } else {
                    $updateData['schedule_in'] = $originalShift->start_time;
                    $updateData['schedule_out'] = $originalShift->end_time;
                    if (!$summary->clock_in) $updateData['status'] = 'alpha';
                }
            } else {
                // Pattern gak ketemu
                $updateData['shift_id'] = null;
                if (!$summary->clock_in) $updateData['status'] = 'off';
            }

            $summary->update($updateData);
        }
    }
}
