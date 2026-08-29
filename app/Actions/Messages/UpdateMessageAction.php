<?php

namespace App\Actions\Messages;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Messages\MessageRepository;
use App\DTO\Messages\UpdateMessageData;
use App\Events\Messages\HouseholdMessageUpdated;
use App\Models\HouseholdMessage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateMessageAction
{
    public function __construct(
        private HouseholdRepository $households,
        private MessageRepository $messages
    ) {}

    public function handle(UpdateMessageData $data): HouseholdMessage
    {
        return DB::transaction(function () use ($data): HouseholdMessage {
            $content = trim($data->content);

            if ($content === '') {
                throw new InvalidArgumentException('Message content cannot be empty.');
            }

            $household = $this->households->findByUuidForUpdate($data->householdUuid);
            $this->households->findMembershipForUpdate($household, $data->actorUserId);
            $message = $this->messages->findForHouseholdForUpdate($household, $data->messageUuid);

            if ($message->sender_id !== $data->actorUserId) {
                throw new AuthorizationException();
            }

            $this->messages->updateContent($message, $content);
            $message->refresh()->load('sender');

            HouseholdMessageUpdated::dispatch($message);

            return $message;
        });
    }
}
