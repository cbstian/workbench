<?php

namespace App\Livewire\Forms;

use App\Enums\WorkspaceRole;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;

/**
 * A user of a workspace and their access (F-04, RN-04, RN-14, RN-26).
 *
 * Three modes share the fields:
 * - create:   a new user managed by the super admin's account;
 * - existing: a user of the account who is not in this workspace yet;
 * - edit:     identity (name, email, password) and access of a member.
 *
 * Admins and members get projects; client users get one or more clients.
 *
 * An admin may only create members, with access to some of their own
 * projects (RN-21, A-14); the rules enforce it whatever the browser sends.
 */
class WorkspaceUserForm extends Form
{
    public const string MODE_CREATE = 'create';

    public const string MODE_EXISTING = 'existing';

    public const string MODE_EDIT = 'edit';

    public string $mode = self::MODE_CREATE;

    public ?Workspace $workspace = null;

    public ?User $user = null;

    public ?int $existing_user_id = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'member';

    /**
     * @var list<int>
     */
    public array $client_ids = [];

    /**
     * @var list<int>
     */
    public array $project_ids = [];

    public function start(string $mode, Workspace $workspace): void
    {
        $this->reset();
        $this->mode = $mode;
        $this->workspace = $workspace;
    }

    public function setUser(User $user, Workspace $workspace): void
    {
        $this->start(self::MODE_EDIT, $workspace);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = ($user->roleIn($workspace) ?? WorkspaceRole::Member)->value;
        $this->client_ids = $user->clients()->where('clients.workspace_id', $workspace->id)->pluck('clients.id')->map(fn ($id): int => (int) $id)->all();
        $this->project_ids = $user->projects()->where('projects.workspace_id', $workspace->id)->pluck('projects.id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * Whether the signed-in user is the super admin of the workspace.
     */
    public function actorIsOwner(): bool
    {
        return $this->workspace !== null && (bool) auth()->user()?->ownsWorkspace($this->workspace);
    }

    public function isClientRole(): bool
    {
        return $this->role === WorkspaceRole::Client->value;
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        $rules = [
            'role' => ['required', new Enum(WorkspaceRole::class)],
            'client_ids' => [Rule::requiredIf($this->isClientRole()), 'array'],
            'client_ids.*' => ['integer', Rule::exists(Client::class, 'id')->where('workspace_id', $this->workspace?->id)->whereNull('deleted_at')],
            'project_ids' => ['array'],
            'project_ids.*' => ['integer', Rule::exists(Project::class, 'id')->where('workspace_id', $this->workspace?->id)->whereNull('deleted_at')],
        ];

        if (! $this->actorIsOwner()) {
            $rules['role'] = ['required', Rule::in([WorkspaceRole::Member->value])];
            $rules['project_ids'] = ['required', 'array', 'min:1'];
            $rules['project_ids.*'] = ['integer', Rule::in(auth()->user()->projectIdsIn($this->workspace))];
        }

        if ($this->mode === self::MODE_EXISTING) {
            return [
                ...$rules,
                'existing_user_id' => ['required', 'integer', function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->addableUsersQuery()->whereKey($value)->exists()) {
                        $fail('Elige un usuario de tu cuenta que aún no esté en este espacio.');
                    }
                }],
            ];
        }

        return [
            ...$rules,
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', function (string $attribute, mixed $value, Closure $fail): void {
                $taken = User::withTrashed()
                    ->whereRaw('lower(email) = ?', [mb_strtolower((string) $value)])
                    ->when($this->user !== null, fn ($query) => $query->whereKeyNot($this->user->id))
                    ->exists();

                if ($taken) {
                    $fail('Ya existe un usuario con ese correo. Si es de tu cuenta, usa «Agregar existente».');
                }
            }],
            'password' => $this->mode === self::MODE_CREATE
                ? ['required', 'string', Password::min(8)]
                : ['nullable', 'string', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'existing_user_id' => 'usuario',
            'name' => 'nombre',
            'email' => 'correo',
            'password' => 'contraseña',
            'role' => 'rol',
            'client_ids' => 'clientes',
            'client_ids.*' => 'cliente',
            'project_ids' => 'proyectos',
            'project_ids.*' => 'proyecto',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'client_ids.required' => 'Un usuario cliente debe pertenecer al menos a un cliente.',
            'role.in' => 'Solo puedes crear miembros.',
            'project_ids.required' => 'Elige al menos uno de tus proyectos.',
            'project_ids.*.in' => 'Solo puedes dar acceso a tus proyectos.',
        ];
    }

    /**
     * Users of the super admin's account that can be added to this workspace.
     *
     * @return Builder<User>
     */
    public function addableUsersQuery(): Builder
    {
        $owner = $this->workspace->account->owner;

        return User::query()
            ->where('account_id', $this->workspace->account_id)
            ->whereKeyNot($owner->id)
            ->whereDoesntHave('workspaces', fn ($query) => $query->whereKey($this->workspace->id));
    }

    /**
     * Create a new user managed by the workspace's account and give them access.
     */
    public function store(User $actor): User
    {
        $validated = $this->validate();

        return DB::transaction(function () use ($validated, $actor): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
            $user->forceFill(['account_id' => $this->workspace->account_id])->save();

            $this->syncAccess($user, $actor);

            return $user;
        });
    }

    /**
     * Add a user of the account to this workspace (RN-26).
     */
    public function addExisting(User $actor): User
    {
        $this->validate();

        $user = User::query()->findOrFail($this->existing_user_id);

        DB::transaction(fn () => $this->syncAccess($user, $actor));

        return $user;
    }

    /**
     * Save identity and access of a member.
     */
    public function update(User $actor): void
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated, $actor): void {
            $this->user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            if (filled($validated['password'])) {
                $this->user->password = $validated['password'];
            }

            $this->user->save();

            $this->syncAccess($this->user, $actor);
        });
    }

    /**
     * Apply the role and its clients or projects in this workspace.
     *
     * Changing between the client role and an internal one first removes the
     * links the new role cannot have (the database guards both, RN-04, RN-18).
     */
    private function syncAccess(User $user, User $actor): void
    {
        $role = WorkspaceRole::from($this->role);
        $currentRole = $user->roleIn($this->workspace);
        $scope = ['workspace_id' => $this->workspace->id, 'user_id' => $user->id];

        if ($currentRole === null) {
            $this->workspace->members()->attach($user->id, ['role' => $role->value, 'created_by_id' => $actor->id]);
        } elseif ($currentRole !== $role) {
            if ($role === WorkspaceRole::Client) {
                DB::table('project_user')->where($scope)->delete();
            } else {
                DB::table('client_user')->where($scope)->delete();
            }

            $this->workspace->members()->updateExistingPivot($user->id, ['role' => $role->value]);
        }

        if ($role === WorkspaceRole::Client) {
            $user->clients()->withPivotValue('workspace_id', $this->workspace->id)->sync($this->client_ids);

            return;
        }

        $current = DB::table('project_user')->where($scope)->pluck('project_id')->map(fn ($id): int => (int) $id)->all();

        DB::table('project_user')->where($scope)->whereNotIn('project_id', $this->project_ids)->delete();

        foreach (array_diff($this->project_ids, $current) as $projectId) {
            $user->projects()->attach($projectId, ['created_by_id' => $actor->id]);
        }
    }
}
