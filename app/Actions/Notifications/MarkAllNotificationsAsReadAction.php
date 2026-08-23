<?php

namespace App\Actions\Notifications;

use App\Contracts\Notifications\NotificationRepository;
use App\Models\User;

final readonly class MarkAllNotificationsAsReadAction
{
    public function __construct(private NotificationRepository $repository) {}

    public function handle(User $user): int
    {
        return $this->repository->markAllAsRead($user);
    }
}
