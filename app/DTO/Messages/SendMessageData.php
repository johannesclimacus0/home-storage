<?php

namespace App\DTO\Messages;

final readonly class SendMessageData
{
    public function __construct(
        public string $householdUuid,
        public int $senderId,
        public string $content
    ) {}
}
