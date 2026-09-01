<?php

namespace App\DTO\Notes;

final readonly class DeleteRecipeNoteData
{
    public function __construct(
        public string $recipeUuid,
        public int $actorUserId,
        public string $noteUuid
    ) {}
}
