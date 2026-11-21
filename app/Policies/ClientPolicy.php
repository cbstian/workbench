<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Models\Workspace;

/**
 * Clients are managed by the super admin only (RN-25, A-20).
 */
class ClientPolicy
{
    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $user->ownsWorkspace($workspace);
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $user->ownsWorkspace($workspace);
    }

    public function update(User $user, Client $client): bool
    {
        return $user->ownsWorkspace($client->workspace);
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->ownsWorkspace($client->workspace);
    }

    public function restore(User $user, Client $client): bool
    {
        return $user->ownsWorkspace($client->workspace);
    }
}
