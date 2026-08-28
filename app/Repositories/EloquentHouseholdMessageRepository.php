<?php

namespace App\Repositories;

use App\Contracts\Messages\MessageRepository;
use App\Models\Household;
use App\Models\HouseholdMessage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\CursorPaginator;

final class EloquentHouseholdMessageRepository implements MessageRepository
{
    public function create(
        Household $household,
        User $sender,
        string $content
    ): HouseholdMessage {
        return $household->messages()->create([
            'sender_id' => $sender->getKey(),
            'content' => $content,
        ]);
    }

    public function paginateForHousehold(Household $household, int $perPage): CursorPaginator {
        return $household->messages()
            ->withTrashed()
            ->with('sender')
            ->latest('id')
            ->cursorPaginate($perPage);
    }

    public function findForHousehold(Household $household, string $messageUuid): HouseholdMessage {
        return $household->messages()
            ->where('uuid', $messageUuid)
            ->with('sender')
            ->firstOrFail();
    }

    public function findForHouseholdForUpdate(Household $household, string $messageUuid): HouseholdMessage
    {
        return $household->messages()
            ->where('uuid', $messageUuid)
            ->with('sender')
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function updateContent(HouseholdMessage $message, string $content): void {
        $message->updateOrFail([
            'content' => $content,
            'edited_at' => CarbonImmutable::now(),
        ]);
    }

    public function delete(HouseholdMessage $message): void
    {
        $message->deleteOrFail();
    }
}
