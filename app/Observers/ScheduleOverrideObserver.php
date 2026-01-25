<?php

namespace App\Observers;

use App\Models\ScheduleOverride;
use App\Models\AttendanceSummary;
use App\Models\Shift;
use App\Models\Employee;
use Carbon\Carbon;
use App\Services\ScheduleGenerator;

class ScheduleOverrideObserver
{
    /**
     * Helper untuk Update Summary (Dipakai berulang)
     */
    private function updateSummary($employeeId, $date, $shiftId = null)
    {
        $summary = AttendanceSummary::where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->first();

        if (!$summary) return;

        // Jika shiftId null, berarti kita mau REVERT ke Pattern Asli
        if ($shiftId === null) {
            $generator = new ScheduleGenerator();
            $employee = Employee::find($employeeId);
            $patternShift = $generator->getPatternShift($employee, Carbon::parse($date));
            $shift = $patternShift; // Bisa null jika pattern gak ada
        } else {
            // Jika ada shiftId, berarti kita pakai Override
            $shift = Shift::find($shiftId);
        }

        // Siapkan Data Update
        $updateData = [];

        if ($shift) {
            $updateData['shift_id'] = $shift->id;

            if ($shift->is_day_off) {
                $updateData['schedule_in'] = null;
                $updateData['schedule_out'] = null;
                // Hanya ubah status jadi OFF jika karyawan belum absen
                if (!$summary->clock_in) $updateData['status'] = 'off';
            } else {
                $updateData['schedule_in'] = $shift->start_time;
                $updateData['schedule_out'] = $shift->end_time;
                // Hanya ubah status jadi ALPHA/TERJADWAL jika karyawan belum absen
                // Dan status sebelumnya adalah OFF (kita bangunkan lagi)
                if (!$summary->clock_in) {
                    $updateData['status'] = 'alpha';
                }
            }
        } else {
            // Kasus langka: Revert ke pattern, tapi patternnya kosong/null
            $updateData['shift_id'] = null;
            $updateData['schedule_in'] = null;
            $updateData['schedule_out'] = null;
            if (!$summary->clock_in) $updateData['status'] = 'off';
        }

        $summary->update($updateData);
    }

    /**
     * Dijalankan saat Create ATAU Edit (Update)
     */
    public function saved(ScheduleOverride $override): void
    {
        // Panggil helper untuk update summary sesuai Shift Baru yang dipilih
        $this->updateSummary($override->employee_id, $override->date, $override->shift_id);
    }

    /**
     * Handle the ScheduleOverride "deleted" event.
     */
    public function deleted(ScheduleOverride $scheduleOverride): void
    {
        // Panggil helper dengan shiftId NULL agar dia REVERT ke Pattern Asli
        $this->updateSummary($scheduleOverride->employee_id, $scheduleOverride->date, null);
    }

    /**
     * Handle the ScheduleOverride "restored" event.
     */
    public function restored(ScheduleOverride $scheduleOverride): void
    {
        $this->saved($scheduleOverride);
    }

    /**
     * Handle the ScheduleOverride "force deleted" event.
     */
    public function forceDeleted(ScheduleOverride $scheduleOverride): void
    {
        //
    }
}
