<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return null;
        }

        $user = DB::transaction(function () {
            return $this->handleRegistration($this->form->getState());
        });

        $user->notify(new \App\Notifications\CustomVerifyEmail);

        $this->form->fill();

        return app(RegistrationResponse::class);
    }

    protected function handleRegistration(array $data): Model
    {
        return $this->getUserModel()::create([
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'tenant_owner',
            'status' => 'inactive',
            'tenant_id' => null,
        ]);
    }
}
