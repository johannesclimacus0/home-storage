<?php

namespace App\Contracts\Messages;

use App\Models\Household;
use App\Models\HouseholdMessage;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;

interface MessageRepository
{
    public function create(
        Household $household,
        User $sender,
        string $content
    ): HouseholdMessage;

    /** @return CursorPaginator<int, HouseholdMessage> */
    public function paginateForHousehold(
        Household $household,
        int $perPage
    ): CursorPaginator;

    public function findForHousehold(
        Household $household,
        string $messageUuid
    ): HouseholdMessage;

    public function findForHouseholdForUpdate(
        Household $household,
        string $messageUuid
    ): HouseholdMessage;

    public function updateContent(
        HouseholdMessage $message,
        string $content
    ): void;

    public function delete(HouseholdMessage $message): void;
}
