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
| `member` | `tasks.view`, `tasks.create`, `tasks.update-assigned`, `wiki.project`, `board.update` |
| `client` | `portal.view`, `portal.comment` (acceso fijo «ver, comentar», P-05) |

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

### Pizarra (E11)

La Pizarra (`/pizarra`) es del Workspace: quien puede ver un Proyecto ve las mismas notas, la misma prioridad, la misma pausa y el mismo orden de filas.

| Acción | Quién |
| --- | --- |
| Ver la Pizarra y sus notas | Super admin, y admin o miembro del equipo del Proyecto (`view-projects`, `Project::visibleTo`). El usuario cliente no entra: el ítem no está en el menú y la página lo rechaza |
| Crear, editar, marcar hecha y eliminar notas (`board.update`) | Super admin, y admin o miembro del equipo. Cualquiera de ellos puede cambiar la nota de otro: es una superficie de trabajo compartida, no un apunte personal. Se guarda `created_by_id` y, al marcarla hecha, `completed_by_id` |
| Pausar, prioridad del Proyecto, fecha de término y orden de filas (`projects.update`) | Super admin y admin del Proyecto. El orden (`projects.board_position`) es compartido; reordenar con un filtro activo usa como vecinos las filas que se están viendo |
| Ocultar o mostrar un Proyecto | Quien puede verlo. Es personal (`board_project_hides`): no cambia la Pizarra de los demás |

Las notas no van a la papelera. `board.update` está en el catálogo de admin y de miembro; el rol cliente sigue sin permisos.

### Usuario cliente: portal (E10)

Las reglas viven en un solo lugar, `App\Support\ClientPortal`; las Policies y el menú le preguntan.

| Qué | Regla | Dónde |
| --- | --- | --- |
| Entrar al portal (`/portal`) | Rol `client` en el Workspace, `portal.view` y al menos un Cliente ahí | Habilidad `view-portal`; `view-dashboard` es su negación (Inicio redirige al portal) |
| Proyectos | De sus Clientes y con `is_visible_to_client` (`Project::scopeVisibleToClient`) | `ProjectPolicy::viewInPortal` |
| Tareas | Todas las Tareas y subtareas de esos Proyectos, solo lectura (P-06). No ve adjuntos ni sus eventos en el historial | `TaskPolicy::viewInPortal` |
| Páginas | Marcadas visibles con todas sus Páginas padre visibles, de esos Proyectos o de la wiki del Workspace (la ven todos los clientes del Workspace, P-14). No ve sus archivos | `PagePolicy::viewInPortal` |
| Conversación con el cliente | Cada Tarea y Página tiene dos hilos (`thread`: `internal` y `client`, P-04). El cliente lee y escribe solo el suyo (`portal.comment`); el equipo lee ambos y escribe en el del cliente mientras el elemento esté visible para él | `viewClientThread`, `commentInClientThread` en `TaskPolicy` y `PagePolicy` |
| Avisos | Solo de comentarios del hilo que puede leer; al abrirlos llega al portal. El equipo llega a la pestaña «Con el cliente» (`?hilo=cliente`) | `NotifyCommentRecipients`, `OpenNotificationController` |

- Sigue sin entrar a ninguna página interna (Proyectos, Tareas, wiki, Pizarra, archivos), aunque el recurso esté visible para su Cliente (A-08): `view-projects` lo excluye y `view`/`update` le niegan cada recurso.
- No crea ni edita nada (A-03); solo comenta, y edita o borra sus propios comentarios.
- Las menciones del hilo del cliente incluyen a los usuarios del Cliente; las del hilo interno, nunca.

### Ver como otro usuario (suplantación)

- El Super admin puede ver la aplicación como cualquier usuario activo de su Cuenta (admin, miembro o usuario cliente) desde **Usuarios → «Ver como este usuario»** (`UserPolicy::impersonate`). No se puede suplantar a uno mismo, a usuarios de otra Cuenta ni a usuarios desactivados, ni encadenar suplantaciones.
- `App\Support\Impersonation` guarda en la sesión al Super admin y su espacio actual; una barra flotante (`<x-app.impersonation-banner />`) indica a quién se está viendo y ofrece «Volver a mi perfil» (`DELETE /impersonation`).
- Lo que se hace mientras tanto queda a nombre del usuario suplantado. Sus datos de cuenta (nombre, correo, contraseña) no se pueden cambiar en ese modo, y si el usuario se desactiva, el Super admin vuelve a su sesión.

## Uso básico del paquete

```php
$user->assignRole('admin');
$user->hasRole('admin');
$user->givePermissionTo('edit tasks');
$user->can('edit tasks');
```

En Blade: `@role('admin') ... @endrole` y `@can('edit tasks') ... @endcan`.

Documentación oficial: https://spatie.be/docs/laravel-permission/v8/introduction
