<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\TracksDeleter;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A client inside a workspace. Projects are optional (RN-12).
 *
 * @property int $id
 * @property string $uuid
 * @property int $workspace_id
 * @property string $name
 * @property string|null $description
 * @property string|null $logo_path
 * @property string|null $color
 * @property int $created_by_id
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable(['workspace_id', 'name', 'description', 'logo_path', 'color', 'created_by_id'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, HasPublicUuid, SoftDeletes, TracksDeleter;

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Client users linked to this client. A user may belong to several clients.
     *
     * @return BelongsToMany<User, $this, ClientMember>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_user')->using(ClientMember::class);
    }
}
