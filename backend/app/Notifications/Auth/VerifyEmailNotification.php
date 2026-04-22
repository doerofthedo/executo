<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

final class VerifyEmailNotification extends VerifyEmail
{
    protected function verificationUrl($notifiable): string
    {
        if (! $notifiable instanceof User) {
            return parent::verificationUrl($notifiable);
        }

        $signedApiUrl = URL::temporarySignedRoute(
            'api.v1.auth.email.verify',
            Carbon::now()->addMinutes(60),
            [
                'user' => $notifiable->ulid,
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        $frontendUrl = rtrim((string) (config('app.frontend_url') ?: config('app.url')), '/');

        return $frontendUrl . '/register?verify=1&url=' . urlencode($signedApiUrl);
    }

    public function toMail($notifiable): MailMessage
    {
        return $this->buildMailMessage($this->verificationUrl($notifiable));
    }
}
