<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

final class VerifyEmailNotification extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return new MailMessage()
            ->subject(__('messages.mail.verify.subject'))
            ->greeting(__('messages.mail.greeting'))
            ->line(__('messages.mail.verify.intro'))
            ->action(__('messages.mail.verify.action'), $url)
            ->line(__('messages.mail.verify.outro'))
            ->salutation(__('messages.mail.salutation').' '.config('app.name'));
    }
}
