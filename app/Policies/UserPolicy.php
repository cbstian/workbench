<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Impersonation;

/**
 * A super admin manages the users their account created (RN-14): edit,
 * deactivate, delete, restore and impersonate them, never themselves.
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

    /**
     * See the app as the user: an active user managed by the super admin's
     * account, never while already impersonating someone.
     */
    public function impersonate(User $actor, User $user): bool
    {
        return $this->managesAccountOf($actor, $user)
            && $user->isActive()
            && ! app(Impersonation::class)->isActive();
    }

    private function managesAccountOf(User $actor, User $user): bool
    {
        return $actor->isNot($user)
            && $actor->ownedAccount !== null
            && $user->account_id === $actor->ownedAccount->id;
    }
}
