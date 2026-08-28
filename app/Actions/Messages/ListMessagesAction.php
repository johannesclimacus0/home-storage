<?php

namespace App\Actions\Messages;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Messages\MessageRepository;
use Illuminate\Pagination\CursorPaginator;

final readonly class ListMessagesAction
{
    public function __construct(
        private HouseholdRepository $households,
        private MessageRepository $messages
    ) {}

    public function handle(string $householdUuid, int $actorUserId, int $perPage): CursorPaginator
    {
        $household = $this->households->findByUuid($householdUuid);
        $this->households->findMembership($household, $actorUserId);

        return $this->messages->paginateForHousehold($household, $perPage);
    }
}
