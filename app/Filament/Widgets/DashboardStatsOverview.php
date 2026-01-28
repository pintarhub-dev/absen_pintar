<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Employee;
use App\Models\AttendanceSummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardStatsOverview extends BaseWidget
{
    // Polling setiap 15 detik biar berasa realtime pas karyawan absen
    protected static ?string $pollingInterval = '15s';

    // Agar widget ini memanjang penuh di atas
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = Carbon::today()->toDateString();

        // 1. Total Karyawan Aktif
        $totalEmployees = Employee::whereNotIn('employment_status', ['resigned', 'terminated', 'retired'])->count();

        // 2. Hadir Hari Ini (Hadir Tepat Waktu + Telat pun dianggap hadir secara fisik)
        $presentToday = AttendanceSummary::where('date', $today)
            ->whereNotNull('clock_in')
            ->count();

        // 3. Terlambat Hari Ini (Penting buat HR!)
        $lateToday = AttendanceSummary::where('date', $today)
            ->where('late_minutes', '>', 0)
            ->count();

        // 4. Sedang Cuti/Sakit/Izin (Status Non-Hadir tapi Valid)
        $onLeaveToday = AttendanceSummary::where('date', $today)
            ->whereIn('status', ['leave', 'sick', 'permit'])
            ->count();

        // --- Logic Chart 7 Hari Terakhir ---
        // Kita ambil data 7 hari kebelakang untuk melihat tren kehadiran
        $attendanceTrend = AttendanceSummary::select(DB::raw('DATE(date) as date'), DB::raw('count(*) as count'))
            ->where('date', '>=', Carbon::now()->subDays(7))
            ->whereNotNull('clock_in')
            ->groupBy('date')
            ->pluck('count')
            ->toArray();

        return [
            Stat::make('Total Karyawan', $totalEmployees)
                ->description('Database Aktif')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Kehadiran Hari Ini', $presentToday)
                ->description($presentToday > 0 ? 'Karyawan sudah check-in' : 'Belum ada absensi berjalan')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart($attendanceTrend),

            Stat::make('Terlambat', $lateToday)
                ->description($lateToday . ' orang terlambat hari ini')
                ->descriptionIcon('heroicon-m-clock')
                ->color($lateToday > 0 ? 'danger' : 'success'),

            Stat::make('Sedang Cuti/Sakit/Izin', $onLeaveToday)
                ->description('Tidak masuk dengan keterangan')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
        ];
    }
}
