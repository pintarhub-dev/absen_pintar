<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\ScheduleOverride;
use App\Observers\ScheduleOverrideObserver;
use App\Models\Holiday;
use App\Observers\HolidayObserver;
use App\Models\Employee;
use App\Observers\EmployeeObserver;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse as RegistrationResponseContract;
use App\Http\Responses\RegistrationResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ScheduleOverride::observe(ScheduleOverrideObserver::class);
        Holiday::observe(HolidayObserver::class);
        Employee::observe(EmployeeObserver::class);
        $this->app->bind(RegistrationResponseContract::class, RegistrationResponse::class);
    }
}
