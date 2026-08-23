<?php

namespace App\Actions\Notifications;

use App\Contracts\Notifications\NotificationRepository;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

final readonly class MarkNotificationAsReadAction
{
    public function __construct(private NotificationRepository $repository)
    {
    }

    public function handle(User $user, string $notificationUuid): DatabaseNotification
    {
        $notification = $this->repository->findForUser($user, $notificationUuid);
        $this->repository->markAsRead($notification);

        return $notification->refresh();
    }
}
