<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

final class VerifyEmailNotification extends VerifyEmail
{
    /**
     * @param  object  $notifiable
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'api.v1.auth.email.verify',
            Carbon::now()->addMinutes(60),
            [
                'user' => $notifiable->ulid,
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }

    /**
     * @param  object  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return $this->buildMailMessage($this->verificationUrl($notifiable));
    }
}
