<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class CustomVerifyEmail extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'filament.admin.auth.email-verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        // TAMPILAN EMAIL
        return (new MailMessage)
            ->subject('Verifikasi Email - Absen Pintar') // Subject Email
            ->greeting('Halo, ' . $notifiable->email . '!')
            ->line('Terima kasih telah mendaftar. Silakan klik tombol di bawah untuk mengaktifkan akun Anda. Link ini hanya berlaku 1 Jam dari saat Email dikirim')
            ->action('Verifikasi Akun Saya', $verificationUrl) // Tombol
            ->line('Jika Anda tidak merasa mendaftar, abaikan pesan ini.');
    }
}
