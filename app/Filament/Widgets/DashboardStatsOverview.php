<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Employee;
use App\Models\Attendance; // Pastikan model Attendance di-import
use App\Models\AttendanceSummary;
use Carbon\Carbon;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalEmployees = Employee::whereNotIn('employment_status', ['resigned', 'terminated', 'retired'])
            ->count();

        $totalEmployeesAttendance = Employee::whereNotIn('employment_status', ['resigned', 'terminated', 'retired'])
            ->whereHas('scheduleAssignments')
            ->count();

        $presentToday = AttendanceSummary::whereDate('date', Carbon::today())
            ->whereNotNull('clock_in')
            ->count();

        $notPresent = $totalEmployeesAttendance - $presentToday;

        return [
            Stat::make('Total Karyawan', $totalEmployees)
                ->description('Karyawan aktif')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Total Karyawan', $totalEmployeesAttendance)
                ->description('Terjadwal Absen')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('primary'),

            Stat::make('Hadir Hari Ini', $presentToday)
                ->description('Sudah Clock-in')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, $presentToday]), //Dummy

            Stat::make('Belum Hadir', $notPresent)
                ->description('Belum Clock-in')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
        ];
    }
}
