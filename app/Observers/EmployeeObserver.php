<?php

namespace App\Observers;

use App\Models\Employee;

class EmployeeObserver
{
    /**
     * Handle Employee "saving" event.
     * Dijalankan SEBELUM data masuk/update ke database.
     */
    public function saving(Employee $employee): void
    {
        // Definisi status yang dianggap "Aktif" (Tidak boleh punya tanggal resign)
        $activeStatuses = ['probation', 'contract', 'permanent', 'internship', 'freelance'];

        // Jika status karyawan termasuk status aktif
        if (in_array($employee->employment_status, $activeStatuses)) {
            $employee->resignation_date = null;
            $employee->resignation_note = null;
        }
    }
}
