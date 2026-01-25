<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchedulePatternResource\Pages;
use App\Models\SchedulePattern;
use App\Models\Shift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class SchedulePatternResource extends Resource
{
    protected static ?string $model = SchedulePattern::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = 'Manajemen Jadwal';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Konfigurasi Pola')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Pola')
                            ->placeholder('Contoh: Security Regu A (5-2)')
                            ->required(),

                        // --- LOGIC AUTO-RESIZE & RE-INDEX ---
                        Forms\Components\TextInput::make('cycle_length')
                            ->label('Panjang Siklus (Hari)')
                            ->numeric()
                            ->default(7)
                            ->minValue(1)
                            ->maxValue(31)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                // 1. Ambil data lama
                                $currentItems = $get('details') ?? [];
                                $targetCount = (int) $state;

                                // Jika data kosong (baru create), inisialisasi array kosong
                                if (!is_array($currentItems)) $currentItems = [];

                                $currentCount = count($currentItems);

                                // 2. Resize Array (Tambah / Potong)
                                if ($targetCount > $currentCount) {
                                    // Tambah baris baru
                                    for ($i = $currentCount; $i < $targetCount; $i++) {
                                        $currentItems[(string) Str::uuid()] = [
                                            'shift_id' => null,
                                            'day_index' => $i + 1 // Isi index sementara
                                        ];
                                    }
                                } elseif ($targetCount < $currentCount) {
                                    $currentItems = array_slice($currentItems, 0, $targetCount);
                                }

                                // 3. RE-INDEXING
                                // Loop ulang semua item untuk memastikan day_index urut 1, 2, 3...
                                $finalItems = [];
                                $counter = 1;
                                foreach ($currentItems as $uuid => $item) {
                                    // Pastikan uuid tetap ada biar filament tidak bingung
                                    // Jika key-nya numeric (dari array_slice), kita generate uuid baru
                                    $key = Str::isUuid($uuid) ? $uuid : (string) Str::uuid();

                                    $item['day_index'] = $counter; // Paksa urut
                                    $finalItems[$key] = $item;
                                    $counter++;
                                }

                                // 4. Simpan ke state
                                $set('details', $finalItems);
                            })
                            ->helperText('Ubah angka ini -> klik di luar -> baris di bawah otomatis menyesuaikan.'),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Jadwal Harian')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->label('Urutan Hari')
                            ->relationship()
                            ->orderColumn('day_index')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Hidden::make('day_index')
                                            ->default(1),

                                        Forms\Components\Placeholder::make('day_label')
                                            ->label('Urutan')
                                            ->content(fn(Get $get) => 'Hari ke-' . $get('day_index')),

                                        Forms\Components\Select::make('shift_id')
                                            ->label('Shift')
                                            ->options(Shift::all()->pluck('label', 'id'))
                                            ->searchable()
                                            ->required(),
                                    ]),
                            ])
                            ->default(function () {
                                $items = [];
                                for ($i = 1; $i <= 7; $i++) {
                                    $items[(string) Str::uuid()] = [
                                        'day_index' => $i,
                                        'shift_id' => null
                                    ];
                                }
                                return $items;
                            })
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pola')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cycle_length')
                    ->label('Siklus')
                    ->suffix(' Hari')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('details_count')
                    ->counts('details')
                    ->label('Jumlah Shift')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => $record->assignments()->count() === 0)
                    ->tooltip(
                        fn($record) =>
                        $record->assignments()->count() > 0
                            ? 'Tidak bisa dihapus karena sudah diassign ke karyawan'
                            : null
                    ),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedulePatterns::route('/'),
            'create' => Pages\CreateSchedulePattern::route('/create'),
            'edit' => Pages\EditSchedulePattern::route('/{record}/edit'),
        ];
    }
}
