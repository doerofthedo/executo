<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

final class ResetPasswordNotification extends ResetPassword
{
    public function __construct(string $token)
    {
        parent::__construct($token);
    }

    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) (config('app.frontend_url') ?: config('app.url')), '/');
        $resetUrl = $frontendUrl . '/reset-password?token=' . urlencode($this->token) . '&email=' . urlencode((string) $notifiable->getEmailForPasswordReset());

        return (new MailMessage())
            ->subject('Reset your password')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset password', $resetUrl)
            ->line('If you did not request a password reset, no further action is required.');
    }
}
