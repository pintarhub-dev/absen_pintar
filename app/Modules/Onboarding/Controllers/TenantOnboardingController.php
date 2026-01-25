<?php

namespace App\Modules\Onboarding\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Onboarding\Requests\TenantOnboardingRequest;
use App\Modules\Onboarding\Services\TenantOnboardingService;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class TenantOnboardingController extends Controller
{
    protected $service;

    public function __construct(TenantOnboardingService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('onboarding.tenant');
    }

    public function store(TenantOnboardingRequest $request)
    {
        $user = Auth::user();
        $this->service->createTenant($request->validated(), $user);

        Notification::make()
            ->title('Selamat Datang!')
            ->body('Perusahaan Anda berhasil dibuat. Silakan mulai pengaturan.')
            ->success()
            ->send();

        return redirect()->route('filament.admin.pages.dashboard');
    }
}
