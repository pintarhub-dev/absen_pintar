<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\AttendanceSummary;
use App\Models\ScheduleOverride;
use App\Models\Shift;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScheduleGenerator
{
    public function generateForEmployee(Employee $employee, Carbon $startDate, Carbon $endDate): void
    {
        // 1. Ambil SEMUA Assignment yang relevan (sebelum end date)
        // Kita butuh collection ini untuk switch pattern di tengah jalan
        $assignments = $employee->scheduleAssignments()
            ->with('pattern.details')
            ->where('effective_date', '<=', $endDate)
            ->orderBy('effective_date', 'asc') // Urutkan dari lama ke baru
            ->get();

        if ($assignments->isEmpty()) {
            return;
        }

        $holidays = $this->getHolidaysInRange($employee->tenant_id, $startDate, $endDate);
        $overrides = $this->getOverridesInRange($employee->id, $startDate, $endDate);

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->toDateString();

            // --- CEK RESIGN ---
            if ($employee->resignation_date && $currentDate->greaterThan($employee->resignation_date)) {
                $currentDate->addDay();
                continue;
            }

            // --- PILIH ASSIGNMENT YANG TEPAT UNTUK TANGGAL INI ---
            // Cari assignment terakhir yang effective_date-nya <= currentDate
            $activeAssignment = $assignments->last(function ($item) use ($currentDate) {
                return $item->effective_date <= $currentDate;
            });

            // Jika pada tanggal ini belum ada assignment yang berlaku (masa lalu sebelum join), skip.
            if (! $activeAssignment) {
                $currentDate->addDay();
                continue;
            }

            // Persiapkan Pattern dari Assignment yang terpilih hari ini
            $patternDetails = $activeAssignment->pattern->details; // Collection sudah di-load
            $cycleLength = $activeAssignment->pattern->cycle_length;

            // Tentukan Shift ID
            $shiftId = $this->resolveShiftId(
                date: $currentDate,
                dateString: $dateString,
                assignment: $activeAssignment, // Pass assignment yang benar
                patternDetails: $patternDetails,
                cycleLength: $cycleLength,
                overrides: $overrides,
                holidays: $holidays
            );

            $shift = $shiftId ? Shift::find($shiftId) : null;

            $this->persistToDatabase($employee, $dateString, $activeAssignment->id, $shift);

            $currentDate->addDay();
        }
    }

    /**
     * Logic resolveShiftId
     */
    private function resolveShiftId(
        Carbon $date,
        string $dateString,
        $assignment,
        Collection $patternDetails,
        int $cycleLength,
        array $overrides,
        array $holidays
    ): ?int {
        // Priority 1: Override
        if (isset($overrides[$dateString])) {
            return $overrides[$dateString];
        }

        // Priority 2: Holiday
        if (in_array($dateString, $holidays, true)) {
            return null;
        }

        // Priority 3: Pattern
        $daysPassed = $assignment->effective_date->diffInDays($date, false);

        if ($daysPassed < 0) return null;

        $cycleDayIndex = (($daysPassed + $assignment->pattern_start_day_index - 1) % $cycleLength) + 1;

        $detail = $patternDetails->firstWhere('day_index', $cycleDayIndex);

        return $detail ? $detail->shift_id : null;
    }

    private function getHolidaysInRange($tenantId, Carbon $start, Carbon $end): array
    {
        return Holiday::where('tenant_id', $tenantId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->toArray();
    }

    private function getOverridesInRange($employeeId, Carbon $start, Carbon $end): array
    {
        return ScheduleOverride::where('employee_id', $employeeId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('shift_id', 'date')
            ->toArray();
    }

    private function persistToDatabase(Employee $employee, string $dateString, int $scheduleId, ?Shift $shift): void
    {
        $initialStatus = 'alpha';
        $scheduleIn = null;
        $scheduleOut = null;

        if (! $shift || $shift->is_day_off) {
            $initialStatus = 'off';
        } else {
            $scheduleIn = $shift->start_time;
            $scheduleOut = $shift->end_time;
        }

        AttendanceSummary::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id'   => $employee->tenant_id,
                'employee_id' => $employee->id,
                'date'        => $dateString,
            ],
            [
                'schedule_id'  => $scheduleId,
                'shift_id'     => $shift?->id,
                'schedule_in'  => $scheduleIn,
                'schedule_out' => $scheduleOut,
                'status'       => DB::raw("CASE WHEN clock_in IS NOT NULL THEN status ELSE '$initialStatus' END"),
            ]
        );
    }

    // Method getPatternShift perlu disesuaikan logic assignmentnya jika mau dipakai observer
    public function getPatternShift(Employee $employee, Carbon $date): ?Shift
    {
        // Cari assignment yang aktif pada tanggal spesifik tersebut
        $assignment = $employee->scheduleAssignments()
            ->with('pattern.details')
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();

        if (! $assignment) return null;

        $patternDetails = $assignment->pattern->details;

        $shiftId = $this->resolveShiftId(
            date: $date,
            dateString: $date->toDateString(),
            assignment: $assignment,
            patternDetails: $patternDetails,
            cycleLength: $assignment->pattern->cycle_length,
            overrides: [],
            holidays: []
        );

        return $shiftId ? Shift::find($shiftId) : null;
    }
}
