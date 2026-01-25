<?php

use Illuminate\Support\Facades\Route;
use Filament\Facades\Filament;
use App\Modules\Onboarding\Controllers\TenantOnboardingController;
use App\Models\User;
use Illuminate\Http\Request;
use Filament\Notifications\Notification;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return redirect()->route('filament.admin.auth.login');
    })->name('login');

    Route::get('/register', function () {
        return redirect()->route('filament.admin.auth.register');
    })->name('register');
});

Route::get('/pending-verification', function () {
    return view('auth.pending-verification');
})->name('auth.pending.verification');

Route::middleware('auth')->group(function () {
    Route::get('/onboarding/tenant', [TenantOnboardingController::class, 'create'])->name('onboarding.tenant.create');
    Route::post('/onboarding/tenant', [TenantOnboardingController::class, 'store'])->name('onboarding.tenant.store');

    Route::post('/logout', function () {
        Filament::auth()->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('filament.admin.auth.login');
    })->name('logout');
});

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::find($id);

    if (! $user) {
        abort(404);
    }

    if (! $request->hasValidSignature()) {
        abort(403, 'Link verifikasi tidak valid atau sudah kadaluarsa.');
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        $user->update(['status' => 'active']);
    }

    session()->flash('notification', [
        'status' => 'success',
        'message' => 'Akun berhasil diverifikasi! Silakan login.'
    ]);

    return redirect()->route('filament.admin.auth.login');
})->middleware(['signed'])->name('filament.admin.auth.email-verification.verify');
