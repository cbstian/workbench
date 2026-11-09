# Permisos (spatie/laravel-permission)

## Instalación

- Paquete: `spatie/laravel-permission` v8 (`composer require spatie/laravel-permission:^8.0`).
- Configuración publicada en `config/permission.php`, con `teams` activado (`'teams' => true`); el resto queda con los valores por defecto del paquete.
- **Teams = workspace:** el equipo de spatie representa el workspace, así que los roles se asignan por workspace. Se usa la columna por defecto `team_id` (`team_foreign_key`), que corresponderá al id del workspace. Aún no hay lógica que fije el team activo (`setPermissionsTeamId()`); se implementa en las etapas del plan.
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

## Roles previstos por el plan

Solo como referencia; todavía no existen roles, permisos, seeders ni policies. Se definen al implementar las etapas de `docs/flujo-canonico.md`.

| Rol | Descripción según el plan |
| --- | --- |
| `super_admin` | Usuario creado por consola. Crea workspaces, clientes y usuarios, y puede desactivarlos o eliminarlos (borrado lógico). |
| `admin` | Hace todas las acciones dentro de sus proyectos. Puede crear miembros con acceso a los proyectos a los que él tiene acceso. No crea proyectos. |
| `member` | Ve los proyectos asignados y todas sus tareas; solo edita las tareas que tiene asignadas. Puede crear tareas y asignarlas a sí mismo o al equipo. |
| `client` | Usuario de un cliente. Por ahora puede acceder, pero no ve nada. |

## Uso básico del paquete

```php
$user->assignRole('admin');
$user->hasRole('admin');
$user->givePermissionTo('edit tasks');
$user->can('edit tasks');
```

En Blade: `@role('admin') ... @endrole` y `@can('edit tasks') ... @endcan`.

Documentación oficial: https://spatie.be/docs/laravel-permission/v8/introduction
