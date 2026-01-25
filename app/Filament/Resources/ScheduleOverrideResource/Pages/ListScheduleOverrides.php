<?php

namespace App\Filament\Resources\ScheduleOverrideResource\Pages;

use App\Filament\Resources\ScheduleOverrideResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use App\Models\Employee;
use App\Models\ScheduleOverride; // Pastikan import Model
use App\Models\AttendanceSummary;
use App\Services\ScheduleGenerator;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ListScheduleOverrides extends ListRecords
{
    protected static string $resource = ScheduleOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Override Manual'),

            // --- TOMBOL SWAP AJAIB ---
            Actions\Action::make('swap_shift')
                ->label('Tukar Shift (Swap)')
                ->icon('heroicon-o-arrows-right-left')
                ->color('warning')
                ->form([
                    Forms\Components\DatePicker::make('date')
                        ->label('Tanggal Tukar')
                        ->required()
                        ->default(now())
                        ->native(false),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('employee_1')
                                ->label('Pihak Pertama')
                                ->relationship(
                                    name: 'employee',
                                    titleAttribute: 'full_name',
                                    modifyQueryUsing: function (Builder $query) {
                                        $query->whereNotIn('employment_status', ['resigned', 'terminated', 'retired']);
                                        $query->whereHas('scheduleAssignments');
                                    }
                                )
                                ->searchable(['full_name', 'nik'])
                                ->required(),

                            Forms\Components\Select::make('employee_2')
                                ->label('Pihak Kedua')
                                ->relationship(
                                    name: 'employee',
                                    titleAttribute: 'full_name',
                                    modifyQueryUsing: function (Builder $query) {
                                        $query->whereNotIn('employment_status', ['resigned', 'terminated', 'retired']);
                                        $query->whereHas('scheduleAssignments');
                                    }
                                )
                                ->searchable(['full_name', 'nik'])
                                ->required()
                                // Validasi: Tidak boleh orang yang sama
                                ->different('employee_1'),
                        ]),

                    Forms\Components\Textarea::make('reason')
                        ->label('Alasan Penukaran')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $date = Carbon::parse($data['date']);
                    $emp1 = Employee::find($data['employee_1']);
                    $emp2 = Employee::find($data['employee_2']);
                    $generator = new ScheduleGenerator();

                    // 1. Cek Shift Asli Keduanya pada tanggal tersebut
                    // (Bisa dari Pattern, atau dari AttendanceSummary jika sudah digenerate)
                    // Kita cari aman: Cek AttendanceSummary dulu (Actual Plan), kalau gak ada baru Pattern.

                    $shift1 = $this->getCurrentShiftId($emp1, $date, $generator);
                    $shift2 = $this->getCurrentShiftId($emp2, $date, $generator);

                    if (!$shift1 || !$shift2) {
                        Notification::make()
                            ->title('Gagal Menukar')
                            ->body('Salah satu karyawan tidak memiliki jadwal pada tanggal tersebut.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // 2. Lakukan SILANG (Swap)
                    // Buat Override untuk Karyawan 1 pakai Shift-nya Karyawan 2
                    ScheduleOverride::create([
                        'employee_id' => $emp1->id,
                        'shift_id' => $shift2, // Pake shift-nya Emp 2
                        'date' => $data['date'],
                        'reason' => "Swap dengan {$emp2->full_name}: " . $data['reason'],
                    ]);

                    // Buat Override untuk Karyawan 2 pakai Shift-nya Karyawan 1
                    ScheduleOverride::create([
                        'employee_id' => $emp2->id,
                        'shift_id' => $shift1, // Pake shift-nya Emp 1
                        'date' => $data['date'],
                        'reason' => "Swap dengan {$emp1->full_name}: " . $data['reason'],
                    ]);

                    Notification::make()
                        ->title('Berhasil Tukar Shift')
                        ->body("Jadwal {$emp1->full_name} dan {$emp2->full_name} telah ditukar.")
                        ->success()
                        ->send();
                }),
        ];
    }

    // Helper untuk mencari ID Shift saat ini (baik dari summary maupun pattern)
    private function getCurrentShiftId($employee, $date, $generator)
    {
        // Cek Summary dulu (karena mungkin sebelumnya sudah ada override lain)
        $summary = AttendanceSummary::where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->first();

        if ($summary && $summary->shift_id) {
            return $summary->shift_id;
        }

        // Kalau belum ada summary, hitung dari Pattern
        $patternShift = $generator->getPatternShift($employee, $date);
        return $patternShift ? $patternShift->id : null;
    }
}
