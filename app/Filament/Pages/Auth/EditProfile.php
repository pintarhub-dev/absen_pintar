<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\Facades\Storage;
use Filament\Resources\Pages\CreateRecord;

class EditProfile extends BaseEditProfile
{
    protected function getRedirectUrl(): string
    {
        return filament()->getUrl();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECTION 1: DATA AKUN (User Table)
                Section::make('Informasi Akun')
                    ->description('Update foto profil dan keamanan akun Anda.')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Foto Profil')
                            ->avatar()
                            ->image()
                            ->imageEditor()
                            ->directory('avatars/' . auth()->user()->tenant_id . '/' . auth()->id())
                            ->visibility('public')
                            ->columnSpan('full')
                            ->alignCenter(),

                        TextInput::make('full_name')
                            ->label('Nama Lengkap')
                            ->required(),

                        TextInput::make('email')
                            ->label('Email Login')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        TextInput::make('password')
                            ->label('Password Baru')
                            ->password()
                            ->confirmed()
                            ->autocomplete('new-password')
                            ->dehydrated(fn($state) => filled($state)) // Cuma diupdate kalau diisi
                            ->required(fn($livewire) => $livewire instanceof CreateRecord),

                        TextInput::make('password_confirmation')
                            ->password()
                            ->label('Konfirmasi Password'),
                    ])->columns(2),

                // SECTION 2: DATA KARYAWAN (Employee Table)
                Section::make('Data Pribadi Karyawan')
                    ->description('Data ini akan tampil di profil karyawan Anda.')
                    ->schema([
                        TextInput::make('employee_nickname')
                            ->label('Nama Panggilan')
                            ->afterStateHydrated(function ($component) {
                                $component->state(auth()->user()->employee?->nickname);
                            }),

                        TextInput::make('employee_phone')
                            ->label('Nomor WhatsApp')
                            ->tel()
                            ->afterStateHydrated(function ($component) {
                                $component->state(auth()->user()->employee?->phone);
                            }),

                        Textarea::make('employee_address')
                            ->label('Alamat Domisili')
                            ->rows(3)
                            ->columnSpanFull()
                            ->afterStateHydrated(function ($component) {
                                $component->state(auth()->user()->employee?->address);
                            }),
                    ])
                    ->visible(fn() => auth()->user()->employee !== null),
            ]);
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Update tabel user
        $record->update($data);

        // Update tabel employee
        if ($record->employee) {
            $record->employee->update([
                'full_name' => $data['full_name'] ?? $record->employee->nickname,
                'nickname' => $data['employee_nickname'] ?? $record->employee->nickname,
                'phone'    => $data['employee_phone'] ?? $record->employee->phone,
                'address'  => $data['employee_address'] ?? $record->employee->address,
            ]);
        }

        return $record;
    }
}
