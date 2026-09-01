<?php

namespace App\DTO\Notes;

final readonly class UpdateRecipeNoteData
{
    public function __construct(
        public string $recipeUuid,
        public int $actorUserId,
        public string $noteUuid,
        public string $content
    ) {}
}
