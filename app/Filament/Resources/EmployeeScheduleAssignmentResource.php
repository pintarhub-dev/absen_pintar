<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeScheduleAssignmentResource\Pages;
use App\Models\EmployeeScheduleAssignment;
use App\Models\SchedulePattern;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeScheduleAssignmentResource extends Resource
{
    protected static ?string $model = EmployeeScheduleAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Manajemen Jadwal';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Penugasan Jadwal')
                    ->description('Tentukan pola kerja apa yang dipakai karyawan dan mulai kapan.')
                    ->schema([
                        // 1. PILIH KARYAWAN
                        Forms\Components\Select::make('employee_id')
                            ->label('Nama Karyawan')
                            ->relationship(
                                name: 'employee',
                                titleAttribute: 'full_name',
                                modifyQueryUsing: function (Builder $query) {
                                    // Hanya karyawan yang employment_status bukan resigned, terminated, retired
                                    $query->whereNotIn('employment_status', ['resigned', 'terminated', 'retired']);
                                    $query->where('is_attendance_required', '1');
                                }
                            )
                            ->getOptionLabelFromRecordUsing(fn(Employee $record) => $record->label)
                            ->searchable([
                                'full_name',
                                'nik',
                            ])
                            ->required(),

                        // 2. PILIH POLA (PATTERN)
                        Forms\Components\Select::make('schedule_pattern_id')
                            ->label('Pilih Pola Jadwal')
                            ->relationship('schedulePattern', 'name')
                            ->getOptionLabelFromRecordUsing(fn(SchedulePattern $record) => $record->label)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live() // Live agar field di bawahnya bisa update otomatis
                            ->afterStateUpdated(function (Set $set) {
                                // Reset index hari jika pola diganti
                                $set('pattern_start_day_index', null);
                            }),

                        // 3. TANGGAL EFEKTIF
                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Berlaku Mulai Tanggal')
                            ->required()
                            ->default(now())
                            ->helperText('Mulai tanggal ini, karyawan akan mengikuti pola tersebut.'),

                        // 4. START INDEX (Magic Dropdown)
                        Forms\Components\Select::make('pattern_start_day_index')
                            ->label('Mulai dari Urutan Hari ke-?')
                            ->required()
                            ->options(function (Get $get) {
                                // Ambil ID Pattern yang sedang dipilih
                                $patternId = $get('schedule_pattern_id');
                                if (! $patternId) return [];

                                // Ambil Detail Pattern-nya
                                $pattern = SchedulePattern::with(['details.shift'])->find($patternId);
                                if (! $pattern) return [];

                                // Buat Opsi: "Hari 1 (Shift Pagi)", "Hari 2 (Shift Malam)"
                                return $pattern->details->mapWithKeys(function ($detail) {
                                    $shiftName = $detail->shift ? $detail->shift->name : 'OFF/Libur';
                                    $label = "Hari ke-{$detail->day_index}";
                                    return [$detail->day_index => $label];
                                })->toArray();
                            })
                            ->disabled(fn(Get $get) => ! $get('schedule_pattern_id')) // Disable kalau belum pilih pola
                            ->helperText('Contoh: Jika tgl efektif jatuh hari Senin, dan di pola Anda Senin adalah Hari ke-1, pilih Hari ke-1.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('schedulePattern.name')
                    ->label('Pola Jadwal')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Mulai Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pattern_start_day_index')
                    ->label('Start Index')
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => "Hari ke-$state"),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('schedule_pattern_id')
                    ->relationship('schedulePattern', 'name')
                    ->label('Filter Pola'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record->hasattendance()->count() === 0)
                    ->tooltip(
                        fn($record) =>
                        $record->hasattendance()->count() > 0
                            ? 'Tidak bisa diubah karena sudah ada data absennya'
                            : null
                    ),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeScheduleAssignments::route('/'),
            'create' => Pages\CreateEmployeeScheduleAssignment::route('/create'),
            'edit' => Pages\EditEmployeeScheduleAssignment::route('/{record}/edit'),
        ];
    }
}
