<?php

use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel(
    'households.{householdUuid}',
    function (User $user, string $householdUuid): bool {
        return Household::query()
            ->where('uuid', $householdUuid)
            ->whereHas(
                'householdMemberships',
                fn ($query) => $query->where(
                    'user_id',
                    $user->getKey()
                )
            )
            ->exists();
    }
);
