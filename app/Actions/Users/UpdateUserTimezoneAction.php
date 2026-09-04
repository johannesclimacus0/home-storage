<?php

namespace App\Actions\Users;

use App\Models\User;

final class UpdateUserTimezoneAction
{
    public function handle(User $user, string $timezone): User
    {
        $user->update(['timezone' => $timezone]);

        return $user->refresh();
    }
}
