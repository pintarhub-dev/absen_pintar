<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string',
        ]);

        // 2. Cek Kredensial (Email & Password)
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password salah.',
            ], 401);
        }

        // 3. Ambil User & Data Karyawan
        $user = User::where('email', $request->email)->first();

        // Cek apakah user ini punya data karyawan?
        if (!$user->employee) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terhubung dengan data Karyawan.',
            ], 403);
        }

        // 4. Generate Token Sanctum
        // Kita beri nama tokennya sesuai device, atau default 'mobile-app'
        $deviceName = $request->device_name ?? 'mobile-app';

        // Hapus token lama jika ingin single-device login (Opsional)
        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
                'employee' => [
                    'id' => $user->employee->id,
                    'nik' => $user->employee->nik,
                    'name' => $user->employee->full_name,
                    'is_flexible' => (bool) $user->employee->is_flexible_location,
                ]
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout Berhasil',
        ]);
    }
}
