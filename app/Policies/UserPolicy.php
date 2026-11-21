<?php

namespace App\Policies;

use App\Models\User;

/**
 * A super admin manages the users their account created (RN-14): edit,
 * deactivate, delete and restore them, never themselves.
 */
class UserPolicy
{
    public function update(User $actor, User $user): bool
    {
        return $this->managesAccountOf($actor, $user);
    }

    public function deactivate(User $actor, User $user): bool
    {
        return $this->managesAccountOf($actor, $user);
    }

    public function delete(User $actor, User $user): bool
    {
        return $this->managesAccountOf($actor, $user);
    }

    public function restore(User $actor, User $user): bool
    {
        return $this->managesAccountOf($actor, $user);
    }

    private function managesAccountOf(User $actor, User $user): bool
    {
        return $actor->isNot($user)
            && $actor->ownedAccount !== null
            && $user->account_id === $actor->ownedAccount->id;
    }
}
