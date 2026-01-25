<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Services\ScheduleGenerator;
use Carbon\Carbon;

class GenerateSchedules extends Command
{
    /**
     * --from untuk manual start date
     */
    protected $signature = 'schedule:generate
                            {--days=30 : Berapa hari ke depan yang mau digenerate}
                            {--from= : (Opsional) Tanggal mulai generate YYYY-MM-DD. Default: Hari ini}
                            {--tenant_id= : (Opsional) Filter tenant tertentu}';

    protected $description = 'Generate jadwal kerja karyawan ke tabel attendance_summaries';

    public function handle(ScheduleGenerator $generator)
    {
        // 1. Tentukan Base Start Date (Titik Awal Global)
        // Jika user isi --from, pakai itu. Jika tidak, pakai Hari Ini.
        $baseStartDate = $this->option('from')
            ? Carbon::parse($this->option('from'))
            : now();

        $days = (int) $this->option('days');

        // Tentukan End Date Global
        // Jika user minta 7 hari, berarti Start + 6 hari
        $globalEndDate = $baseStartDate->copy()->addDays($days - 1);

        $this->info("Memulai generate jadwal...");
        $this->info("Periode Global: " . $baseStartDate->toDateString() . " s/d " . $globalEndDate->toDateString());

        // 2. Query Employee
        $query = Employee::query();

        $query->where(function ($q) use ($baseStartDate) {
            $q->whereNull('resignation_date')
                ->orWhere('resignation_date', '>=', $baseStartDate); // Hanya yang resign setelah tanggal generate
        });

        if ($this->option('tenant_id')) {
            $query->where('tenant_id', $this->option('tenant_id'));
        }

        // Optimasi: Eager Load assignment terbaru untuk pengecekan tanggal
        $employees = $query->with(['scheduleAssignments' => function ($q) {
            $q->latest('effective_date');
        }])->get();

        $bar = $this->output->createProgressBar($employees->count());
        $bar->start();

        foreach ($employees as $employee) {
            // --- Tentukan Start Date Per Karyawan ---

            // Ambil assignment terakhir/aktif
            $latestAssignment = $employee->scheduleAssignments->first();

            // Jika karyawan belum punya pattern sama sekali, skip.
            if (!$latestAssignment) {
                $bar->advance();
                continue;
            }

            $effectiveDate = $latestAssignment->effective_date;

            // Kapan kita mulai generate untuk Karyawan?
            // Opsi A: Default (Base Start Date)
            $employeeStartDate = $baseStartDate->copy();

            // Opsi B: Jika Karyawan baru masuk minggu depan (Effective Date > Base Date),
            // Jangan buang waktu loop dari hari ini. Mulailah dari tanggal efektif dia.
            if ($effectiveDate > $employeeStartDate) {
                $employeeStartDate = $effectiveDate->copy();
            }

            // Cek Resign (Jika sudah ada logika resign)
            if ($employee->resignation_date && $employee->resignation_date < $employeeStartDate) {
                // Skip jika dia sudah resign sebelum tanggal mulai generate
                $bar->advance();
                continue;
            }

            // Validasi Akhir: Pastikan Start tidak melebihi End Global
            if ($employeeStartDate > $globalEndDate) {
                // Kasus: Kita mau generate 7 hari kedepan, tapi Karyawan baru masuk bulan depan.
                // skip karyawan.
                $bar->advance();
                continue;
            }

            // Jalankan Generator
            $generator->generateForEmployee($employee, $employeeStartDate, $globalEndDate);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Jadwal berhasil digenerate!');
    }
}
