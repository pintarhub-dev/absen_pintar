<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkLocationResource\Pages;
use App\Models\WorkLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkLocationResource extends Resource
{
    protected static ?string $model = WorkLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Data Master';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Lokasi Kantor')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Kantor / Cabang')
                            ->required(),

                        Forms\Components\TextInput::make('address')
                            ->label('Alamat Lengkap')
                            ->columnSpanFull(),

                        // KOORDINAT
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->numeric()
                                    ->required()
                                    ->helperText('Contoh: -6.200000'),

                                Forms\Components\TextInput::make('longitude')
                                    ->numeric()
                                    ->required()
                                    ->helperText('Contoh: 106.816666'),
                            ]),

                        Forms\Components\TextInput::make('radius')
                            ->label('Radius Absensi (Meter)')
                            ->numeric()
                            ->default(50)
                            ->helperText('Jarak maksimal karyawan boleh absen dari titik pusat.'),

                        Forms\Components\Select::make('timezone')
                            ->label('Zona Waktu (Timezone)')
                            ->options(function () {
                                // Helper PHP untuk ambil semua timezone dunia
                                // Kita jadikan Key = Value agar mudah
                                $identifiers = \DateTimeZone::listIdentifiers();
                                return array_combine($identifiers, $identifiers);
                            })
                            ->searchable()
                            ->required()
                            ->default('Asia/Jakarta')
                            ->placeholder('Pilih Timezone Lokal'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('radius')->suffix(' meter'),
                Tables\Columns\TextColumn::make('latitude')->label('Lat'),
                Tables\Columns\TextColumn::make('longitude')->label('Long'),
                Tables\Columns\TextColumn::make('timezone')->label('Timezone'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tombol pintas buka Google Maps
                Tables\Actions\Action::make('open_map')
                    ->label('Lihat Peta')
                    ->icon('heroicon-o-map')
                    ->url(fn($record) => "https://www.google.com/maps/search/?api=1&query={$record->latitude},{$record->longitude}")
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkLocations::route('/'),
            'create' => Pages\CreateWorkLocation::route('/create'),
            'edit' => Pages\EditWorkLocation::route('/{record}/edit'),
        ];
    }
}
