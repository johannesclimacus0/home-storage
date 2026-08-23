<?php

namespace App\Actions\Notifications;

use App\Contracts\Notifications\NotificationRepository;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ListNotificationsAction
{
    public function __construct(private NotificationRepository $repository) {}

    public function handle(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginateForUser($user, $perPage);
    }
}
