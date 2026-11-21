<x-ui.page>
    <x-ui.page-header title="Roles y permisos"
                      description="Qué puede hacer cada rol dentro de un espacio de trabajo. El rol se asigna por espacio al crear o editar un usuario." />

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-line bg-surface p-4">
            <x-badge text="Super admin" color="primary" round />
            <p class="mt-2 text-sm text-ink-muted">Dueño de la cuenta. Crea espacios, clientes, proyectos y usuarios, y tiene todos los permisos en sus espacios.</p>
        </div>
        @foreach ($this->roles as $role)
            <div wire:key="role-{{ $role->value }}" class="rounded-lg border border-line bg-surface p-4">
                <x-badge :text="$role->label()" :color="$role->color()" light round />
                <p class="mt-2 text-sm text-ink-muted">{{ $role->description() }}</p>
            </div>
        @endforeach
    </div>

    <x-ui.section title="Permisos por rol" description="Aplican solo en los proyectos a los que el usuario está asignado.">
        <div class="overflow-x-auto rounded-lg border border-line bg-surface">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-line text-xs text-ink-muted">
                        <th class="px-4 py-2.5 text-left font-medium">Permiso</th>
                        <th class="px-4 py-2.5 text-center font-medium">Super admin</th>
                        @foreach ($this->roles as $role)
                            <th class="px-4 py-2.5 text-center font-medium">{{ $role->label() }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($this->permissions as $permission)
                        <tr wire:key="permission-{{ $permission->value }}">
                            <td class="px-4 py-2.5">
                                <p class="text-ink">{{ $permission->label() }}</p>
                                <p class="font-mono text-xs text-ink-subtle">{{ $permission->value }}</p>
                            </td>
                            <td class="px-4 py-2.5 text-center"><x-icon icon="check" class="mx-auto size-4 text-ink" aria-label="Sí" /></td>
                            @foreach ($this->roles as $role)
                                <td class="px-4 py-2.5 text-center">
                                    @if (in_array($permission->value, $this->grants[$role->value] ?? [], true))
                                        <x-icon icon="check" class="mx-auto size-4 text-ink" aria-label="Sí" />
                                    @else
                                        <span class="text-ink-subtle" aria-label="No">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.section>

    <x-ui.section title="Exclusivo del Super admin" description="Nunca se otorga a un rol.">
        <ul class="grid gap-2 sm:grid-cols-2">
            @foreach (\App\Enums\Permission::SUPER_ADMIN_ONLY as $ability)
                <li class="flex items-start gap-2 text-sm text-ink">
                    <x-icon icon="lock-closed" class="mt-0.5 size-4 shrink-0 text-ink-subtle" />
                    {{ $ability }}
                </li>
            @endforeach
        </ul>
    </x-ui.section>
</x-ui.page>
