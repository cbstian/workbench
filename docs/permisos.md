# Permisos (spatie/laravel-permission)

## Instalación

- Paquete: `spatie/laravel-permission` v8 (`composer require spatie/laravel-permission:^8.0`).
- Configuración publicada en `config/permission.php`, con `teams` activado (`'teams' => true`); el resto queda con los valores por defecto del paquete.
- **Teams = workspace:** el equipo de spatie representa el workspace, así que los roles se asignan por workspace. Se usa la columna por defecto `team_id` (`team_foreign_key`), que corresponderá al id del workspace. El team se fija por consulta con `User::withinWorkspaceTeam()`; no hay un team global por request.
- Migración publicada en `database/migrations/*_create_permission_tables.php` y ejecutada. Crea las tablas `roles`, `permissions`, `model_has_roles`, `model_has_permissions` y `role_has_permissions`, con las columnas `team_id` de teams.
- El modelo `App\Models\User` usa el trait `Spatie\Permission\Traits\HasRoles`.

Para volver a publicar los archivos del paquete:

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Si se cambian roles o permisos directo en la base de datos, limpiar la caché del paquete:

```bash
php artisan permission:cache-reset
```

## Modelo de autorización (implementado)

| Pieza | Dónde | Qué hace |
| --- | --- | --- |
| Super admin | `accounts.owner_id` | Es Super admin quien es dueño de la Cuenta del Workspace (`User::ownsWorkspace()`). No es un rol de spatie: tiene todas las habilidades en los Workspaces de su Cuenta y ninguna en los demás |
| Roles `admin`, `member`, `client` | `roles` con `team_id = null` | Roles globales de spatie, usables en cualquier Workspace (team). Se crean con `App\Actions\Authorization\SyncRolePermissions` (migración `seed_workspace_roles_and_permissions` y `DatabaseSeeder`) |
| Catálogo de permisos | `App\Enums\Permission` + `WorkspaceRole::permissions()` | Lo que cada rol hace dentro de sus Proyectos asignados. Tras cambiarlo, volver a correr `SyncRolePermissions` |
| Rol por Workspace | `workspace_user.role` → `model_has_roles.team_id` | `workspace_user.role` es la fuente de verdad (D-04). `WorkspaceMember` (pivote) sincroniza el rol de spatie del team al crear, cambiar o quitar la membresía |
| Chequeo | `User::hasWorkspacePermission($workspace, Permission::X)` | Fija el team del Workspace, consulta a spatie y restaura el team anterior |
| Policies | `app/Policies` | `WorkspacePolicy`, `ClientPolicy`, `ProjectPolicy`, `UserPolicy`: Super admin por dueño de la Cuenta; admin/miembro por equipo del Proyecto + permiso del rol |
| Habilidades del menú | `AppServiceProvider` | `manage-account` (es Super admin), `manage-workspace` (dueño del Workspace actual), `view-members` y `view-trash` (Super admin, o admin con `members.create` / `trash.project`), usadas en `config/navigation.php` |

Lo exclusivo del Super admin (crear Workspaces, Clientes, Proyectos y usuarios, agregar usuarios existentes, desactivar o eliminar usuarios, wiki del Workspace) nunca se otorga a un rol: se lista en `Permission::SUPER_ADMIN_ONLY` y lo aplican las Policies. La página **Roles y permisos** (`/roles`) muestra la matriz leída desde spatie.

| Rol | Permisos |
| --- | --- |
| `admin` | todos los del catálogo |
| `member` | `tasks.view`, `tasks.create`, `tasks.update-assigned`, `wiki.project` |
| `client` | ninguno (por ahora accede sin ver nada) |

### Perfil Admin

- Ve solo los Proyectos de su equipo (`Project::scopeVisibleTo`) y los configura (`projects.update`, `projects.configure`); no crea ni borra Proyectos ni maneja su equipo.
- **Usuarios** (`members.create`): ve los equipos de sus Proyectos y crea miembros nuevos con acceso a uno o más de sus Proyectos. `WorkspaceUserForm` fuerza el rol `member` y valida que los Proyectos sean suyos; no agrega usuarios existentes ni edita, desactiva o elimina usuarios (A-14, A-21).
- **Papelera** (`trash.project`): ve y restaura lo borrado dentro de sus Proyectos; Clientes, Proyectos y etiquetas de wiki quedan para el Super admin.

### Tareas (`TaskPolicy`)

- Ver: Super admin y equipo del Proyecto con `tasks.view` (admin y miembro). El usuario cliente no ve nada.
- Crear: Super admin y equipo con `tasks.create`. Quien crea desde el equipo queda preseleccionado como asignado.
- Editar, enviar a la papelera y agregar subtareas: Super admin y admin del Proyecto (`tasks.update-any`); el miembro solo en tareas asignadas a él o cuya tarea padre tiene asignada (`tasks.update-assigned`, RN-23).
- Las tareas de un Proyecto en la papelera, y las subtareas de una tarea en la papelera, quedan ocultas.
- Comentar y adjuntar (`collaborate`): quien puede editar la tarea (F-07). El resto del equipo ve comentarios y adjuntos y descarga los archivos.
- Editar un comentario: solo su autor. Eliminar un comentario o adjunto: su autor, o el Super admin y los admins del Proyecto (`moderate`). Ambos van a la papelera.
- Los adjuntos se guardan en el disco privado `local` (`workspaces/{uuid}/tasks/{uuid}/{uuid}`, hasta 10 MB) y se descargan por `attachments.show`, que exige poder ver la tarea; un adjunto en la papelera no se sirve.

### Wiki (`PagePolicy`)

- Wiki del Workspace (`/wiki`, páginas sin Proyecto): solo el Super admin (RN-24, A-18).
- Wiki de un Proyecto (`/projects/{uuid}/wiki`): Super admin y equipo con `wiki.project` (admin y miembro). El usuario cliente no entra.
- Quien entra a una wiki crea, edita, mueve, etiqueta y envía a la papelera sus Páginas, y sube o borra archivos.
- Las subpáginas de una Página en la papelera quedan ocultas hasta restaurarla.

### Usuario cliente (E10)

- Entra con su correo y contraseña y ve solo Inicio (con los Clientes a los que pertenece) y Ajustes de su perfil.
- No ve Proyectos, Tareas, wiki ni archivos, aunque estén marcados como visibles para su Cliente (A-08): la habilidad `view-projects` lo excluye y las Policies le niegan cada recurso.
- No crea ni edita nada (A-03). Las marcas de visibilidad quedan registradas para cuando se defina qué ve el cliente (P-04, P-05, P-06, P-14).

## Uso básico del paquete

```php
$user->assignRole('admin');
$user->hasRole('admin');
$user->givePermissionTo('edit tasks');
$user->can('edit tasks');
```

En Blade: `@role('admin') ... @endrole` y `@can('edit tasks') ... @endcan`.

Documentación oficial: https://spatie.be/docs/laravel-permission/v8/introduction
