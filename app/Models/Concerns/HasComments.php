<?php

namespace App\Models\Concerns;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Shared half of {@see Commentable}: followers and the mentionable filter.
 *
 * @mixin Model
 */
trait HasComments
{
    /**
     * Name of the followers pivot table.
     */
    abstract protected function followersTable(): string;

    /**
     * @return BelongsToMany<User, $this>
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, $this->followersTable())
            ->withPivot('is_following')
            ->withTimestamps();
    }

    /**
     * Follow or stop following. The row stays when unfollowing so automatic
     * following does not add the user back.
     */
    public function setFollowing(User $user, bool $isFollowing): void
    {
        $this->followers()->syncWithoutDetaching([$user->id => ['is_following' => $isFollowing]]);
    }

    /**
     * Follow unless the user explicitly unfollowed before.
     *
     * @param  list<int>  $userIds
     */
    public function followIfNotOptedOut(array $userIds): void
    {
        $known = $this->followers()->whereIn('users.id', $userIds)->pluck('users.id')->all();
        $new = array_values(array_diff(array_unique($userIds), $known));

        if ($new !== []) {
            $this->followers()->attach(array_fill_keys($new, ['is_following' => true]));
        }
    }

    public function isFollowedBy(User $user): bool
    {
        $choice = $this->followers()->whereKey($user->id)->first()?->pivot?->is_following;

        return $choice !== null ? (bool) $choice : in_array($user->id, $this->implicitFollowerIds(), true);
    }

    /**
     * Everyone following now: implicit followers plus explicit ones, minus
     * those who unfollowed.
     *
     * @return list<int>
     */
    public function followerIds(): array
    {
        $choices = $this->followers()->pluck('is_following', 'users.id');
        $optedOut = $choices->reject(fn ($isFollowing): bool => (bool) $isFollowing)->keys()->map(fn ($id): int => (int) $id)->all();
        $explicit = $choices->filter(fn ($isFollowing): bool => (bool) $isFollowing)->keys()->map(fn ($id): int => (int) $id)->all();

        return array_values(array_diff(array_unique([...$this->implicitFollowerIds(), ...$explicit]), $optedOut));
    }

    /**
     * Active candidates who may view this element, without duplicates.
     *
     * @param  iterable<int, User|null>  $candidates
     * @return Collection<int, User>
     */
    protected function filterMentionable(iterable $candidates): Collection
    {
        return Collection::make($candidates)
            ->filter(fn (?User $user): bool => $user !== null && $user->isActive())
            ->unique('id')
            ->filter(fn (User $user): bool => $user->can('view', $this))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Super admin of the account that holds the workspace.
     */
    protected function accountOwner(int $workspaceId): ?User
    {
        return Account::query()
            ->whereHas('workspaces', fn ($query) => $query->whereKey($workspaceId))
            ->first()?->owner;
    }
}
