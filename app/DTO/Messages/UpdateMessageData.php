<?php

namespace App\DTO\Messages;

final readonly class UpdateMessageData
{
    public function __construct(
        public string $householdUuid,
        public int $actorUserId,
        public string $messageUuid,
        public string $content
    ) {}
}
