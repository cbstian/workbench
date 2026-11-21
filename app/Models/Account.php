<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * The scope of one super admin (glossary: «Cuenta»): their workspaces and the
 * users created in them. Owning an account makes a user a super admin (RN-01).
 *
 * @property int $id
 * @property string $uuid
 * @property int $owner_id
 * @property string $name
 */
#[Fillable(['owner_id', 'name'])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, HasPublicUuid;

    /**
     * Open the account of a user created from the console, making them its super admin.
     */
    public static function openFor(User $owner, ?string $name = null): self
    {
        return DB::transaction(function () use ($owner, $name): self {
            $account = self::query()->create([
                'owner_id' => $owner->id,
                'name' => $name ?? $owner->name,
            ]);

            $owner->forceFill(['account_id' => $account->id])->save();

            return $account;
        });
    }

    /**
     * Super admin who owns the account.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Users created and managed by this account, including its owner.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Workspace, $this>
     */
    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }
}
