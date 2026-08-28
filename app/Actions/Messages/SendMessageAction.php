<?php

namespace App\Actions\Messages;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Messages\MessageRepository;
use App\DTO\Messages\SendMessageData;
use App\Events\Messages\HouseholdMessageSent;
use App\Models\HouseholdMessage;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class SendMessageAction
{
    public function __construct(
        private HouseholdRepository $households,
        private MessageRepository $messages
    ) {}

    public function handle(SendMessageData $data): HouseholdMessage
    {
        return DB::transaction(function () use ($data): HouseholdMessage {
            $content = trim($data->content);

            if ($content === '') {
                throw new InvalidArgumentException('Message content cannot be empty.');
            }

            $household = $this->households->findByUuidForUpdate($data->householdUuid);
            $membership = $this->households->findMembershipForUpdate($household, $data->senderId);
            $message = $this->messages->create($household, $membership->user, $content);
            $message->load(['household', 'sender']);

            HouseholdMessageSent::dispatch($message);

            return $message;
        });
    }
}
