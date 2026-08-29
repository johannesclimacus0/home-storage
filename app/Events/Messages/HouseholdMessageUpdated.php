<?php

namespace App\Events\Messages;

use App\Models\HouseholdMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class HouseholdMessageUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly HouseholdMessage $message
    ) {}

    public function broadcastOn()
    {
        return new PrivateChannel(
            'households.' . $this->message->household->uuid
        );
    }

    public function broadcastAs(): string
    {
        return 'household.message.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'uuid' => $this->message->uuid,
                'content' => $this->message->content,
                'edited_at' => $this->message->edited_at?->toISOString(),
            ],
        ];
    }
}
