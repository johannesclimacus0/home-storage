<?php

namespace App\DTO\Notes;

final readonly class CreateRecipeNoteData
{
    public function __construct(
        public string $recipeUuid,
        public int $actorUserId,
        public string $content
    ) {}
}
