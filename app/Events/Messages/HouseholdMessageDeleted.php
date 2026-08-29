<?php

namespace App\Events\Messages;

use App\Models\HouseholdMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HouseholdMessageDeleted implements ShouldDispatchAfterCommit, ShouldBroadcast
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
        return 'household.message.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'uuid' => $this->message->uuid,
                'deleted_at' => $this->message->deleted_at?->toISOString()
            ]
        ];
    }
}
