<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleOverrideResource\Pages;
use App\Models\ScheduleOverride;
use App\Models\Employee;
use App\Models\Shift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\ScheduleGenerator;
use Closure;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;

class ScheduleOverrideResource extends Resource
{
    protected static ?string $model = ScheduleOverride::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square'; // Ikon tukar/switch
    protected static ?string $navigationLabel = 'Tukar Shift / Override';
    protected static ?string $navigationGroup = 'Manajemen Jadwal';
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Formulir Perubahan Jadwal')
                    ->description('Gunakan ini untuk tukar shift, lembur di hari libur, atau perubahan mendadak lainnya.')
                    ->schema([
                        // 1. Pilih Karyawan
                        Forms\Components\Select::make('employee_id')
                            ->label('Karyawan')
                            ->relationship(
                                name: 'employee',
                                titleAttribute: 'full_name',
                                modifyQueryUsing: function (Builder $query) {
                                    $query->whereNotIn('employment_status', ['resigned', 'terminated', 'retired']);
                                    $query->whereHas('scheduleAssignments');
                                }
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn(Employee $record) => $record->label
                            )
                            ->searchable(['full_name', 'nik'])
                            ->required()
                            ->live(),

                        // 2. Pilih Tanggal
                        Forms\Components\DatePicker::make('date')
                            ->label('Tanggal Perubahan')
                            ->required()
                            ->native(false)
                            ->rule(function (Get $get, $record) {
                                // Ambil ID Employee yang sedang dipilih
                                $employeeId = $get('employee_id');

                                // Kita buat aturan Unik Custom
                                return Rule::unique(ScheduleOverride::class, 'date')
                                    ->where('employee_id', $employeeId)
                                    ->withoutTrashed()
                                    // Abaikan record ini sendiri jika sedang mode Edit
                                    ->ignore($record);
                            })
                            ->validationMessages([
                                'unique' => 'Karyawan ini SUDAH memiliki perubahan jadwal di tanggal tersebut. Silakan edit data yang sudah ada.',
                            ]),

                        // 3. Pilih Shift Baru (Target)
                        Forms\Components\Select::make('shift_id')
                            ->label('Shift Pengganti')
                            ->required()
                            // Gunakan accessor 'label' yang sudah kita buat di Model Shift
                            ->options(Shift::all()->pluck('label', 'id'))
                            ->searchable()
                            ->rule(function (Forms\Get $get) {
                                return function (string $attribute, $value, Closure $fail) use ($get) {
                                    // 1. Ambil data form
                                    $employeeId = $get('employee_id');
                                    $date = $get('date');

                                    if (!$employeeId || !$date) return; // Skip kalau data belum lengkap

                                    // 2. Hitung Jadwal Asli (Pattern)
                                    $generator = new ScheduleGenerator();
                                    $employee = Employee::find($employeeId);
                                    $originalShift = $generator->getPatternShift($employee, Carbon::parse($date));

                                    // 3. Bandingkan
                                    // Jika jadwal asli null (tidak ada), tidak perlu validasi (bebas isi)
                                    // Jika ada, cek apakah ID-nya sama dengan yang dipilih ($value)
                                    if ($originalShift && $originalShift->id == $value) {
                                        $fail('Shift yang dipilih SAMA dengan jadwal asli karyawan tersebut. Tidak perlu override.');
                                    }
                                };
                            })
                            ->helperText('Shift ini akan menimpa jadwal asli pada tanggal tersebut.'),

                        // 4. Alasan / Catatan (Opsional tapi penting)
                        Forms\Components\Textarea::make('reason')
                            ->label('Catatan')
                            ->placeholder('Contoh: Tukar shift dengan Agus karena sakit')
                            ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('shift.name')
                    ->label('Shift Baru')
                    ->badge()
                    ->color('warning'), // Warna kuning menandakan ini perubahan/override

                Tables\Columns\TextColumn::make('reason')
                    ->label('Catatan')
                    ->limit(30),
            ])
            ->filters([
                // Filter berdasarkan Karyawan
                Tables\Filters\SelectFilter::make('employee_id')
                    ->relationship('employee', 'full_name')
                    ->label('Karyawan'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScheduleOverrides::route('/'),
            'create' => Pages\CreateScheduleOverride::route('/create'),
            'edit' => Pages\EditScheduleOverride::route('/{record}/edit'),
        ];
    }
}
