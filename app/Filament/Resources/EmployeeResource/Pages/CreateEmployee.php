<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Ambil data User dari form
        $userData = [
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'employee',
            'status' => 'active',
            'tenant_id' => auth()->user()->tenant_id, // Wajib set manual karena User::create ga otomatis kena Global Scope
        ];

        // 2. Buat User Baru (Gunakan Transaction biar aman)
        $user = DB::transaction(function () use ($userData) {
            return User::create($userData);
        });

        // 3. Masukkan user_id ke array data Employee
        $data['user_id'] = $user->id;
        $data['tenant_id'] = auth()->user()->tenant_id; // Explicitly set tenant for employee too

        // 4. Hapus field 'email' dan 'password' dari array
        // Karena tabel employees tidak punya kolom ini. Kalau tidak dihapus, error SQL.
        unset($data['email']);
        unset($data['password']);

        if ($data['is_flexible_location']) {
            $data['work_location_id'] = null;
        }

        return $data;
    }
}
