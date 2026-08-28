<?php

namespace App\Events\Messages;

use App\Models\HouseholdMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class HouseholdMessageSent implements ShouldDispatchAfterCommit, ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly HouseholdMessage $message
    )
    {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(
            'households.'.$this->message->household->uuid
        );
    }

    public function broadcastAs(): string
    {
        return 'household.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'uuid' => $this->message->uuid,
                'content' => $this->message->content,
                'sender' => [
                    'id' => $this->message->sender->getKey(),
                    'name' => $this->message->sender->name,
                ],
                'edited_at' => null,
                'deleted_at' => null,
                'created_at' => $this->message->created_at->toISOString(),
            ],
        ];
    }
}
