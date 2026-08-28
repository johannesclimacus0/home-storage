<?php

namespace App\Actions\Messages;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Messages\MessageRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class DeleteMessageAction
{
    public function __construct(
        private HouseholdRepository $households,
        private MessageRepository $messages
    ) {}

    public function handle(string $householdUuid, int $actorUserId, string $messageUuid): void
    {
        DB::transaction(function () use ($householdUuid, $actorUserId, $messageUuid): void {
            $household = $this->households->findByUuidForUpdate($householdUuid);
            $this->households->findMembershipForUpdate($household, $actorUserId);
            $message = $this->messages->findForHouseholdForUpdate($household, $messageUuid);

            if ($message->sender_id !== $actorUserId) {
                throw new AuthorizationException();
            }

            $this->messages->delete($message);
        });
    }
}
