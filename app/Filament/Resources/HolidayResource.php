<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HolidayResource\Pages;
use App\Models\Holiday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Carbon\Carbon;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Data Master';
    protected static ?string $navigationLabel = 'Hari Libur';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Hari Libur')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Libur')
                            ->placeholder('Contoh: Tahun Baru Imlek')
                            ->required(),

                        Forms\Components\DatePicker::make('date')
                            ->label('Tanggal')
                            ->required()
                            ->native(false)
                            ->unique(ignoreRecord: true), // Satu tanggal satu libur

                        Forms\Components\Select::make('type')
                            ->label('Jenis Libur')
                            ->options([
                                'national' => 'Nasional (Tanggal Merah)',
                                'collective' => 'Cuti Bersama',
                                'company' => 'Libur Perusahaan (Internal)',
                            ])
                            ->default('national')
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Libur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->description(fn($record) => $record->date->locale('id')->dayName)
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'national' => 'danger',
                        'collective' => 'warning',
                        'company' => 'info',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'national' => 'Nasional',
                        'collective' => 'Cuti Bersama',
                        'company' => 'Internal',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'national' => 'Nasional',
                        'collective' => 'Cuti Bersama',
                        'company' => 'Internal',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'edit' => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}
