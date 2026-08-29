<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

final class ResetPasswordNotification extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $expire = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

        return new MailMessage()
            ->subject(__('messages.mail.password_reset.subject'))
            ->greeting(__('messages.mail.greeting'))
            ->line(__('messages.mail.password_reset.intro'))
            ->action(__('messages.mail.password_reset.action'), $url)
            ->line(__('messages.mail.password_reset.expires', ['count' => $expire]))
            ->line(__('messages.mail.password_reset.outro'))
            ->salutation(__('messages.mail.salutation') . ' ' . config('app.name'));
    }
}
