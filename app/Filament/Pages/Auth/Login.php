<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Facades\Filament;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $data = $this->form->getState();

            if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
                $this->throwFailureValidationException();
            }

            $user = Filament::auth()->user();

            // Cek 1: Employee
            if ($user->role === 'employee' && $user->employee) {
                if (! $user->employee->is_access_web) {
                    Filament::auth()->logout();
                    throw ValidationException::withMessages([
                        'data.email' => 'Akses Ditolak. Karyawan hanya boleh login lewat Aplikasi HP.',
                    ]);
                }
            }

            // Cek 2: Inactive
            if ($user->status !== 'active') {
                Filament::auth()->logout();

                throw ValidationException::withMessages([
                    'data.email' => 'Akun Anda tidak aktif.',
                ]);
            }

            session()->regenerate();

            return app(LoginResponse::class);
        } catch (ValidationException $e) {
            throw $e;
        }
    }
}
