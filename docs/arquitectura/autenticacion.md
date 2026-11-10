# Autenticación (Laravel Fortify)

## Qué está activo

| Función | Estado | Motivo |
| --- | --- | --- |
| Inicio de sesión | ✅ | |
| Recuperación de contraseña (olvidé / restablecer) | ✅ | |
| Cerrar sesión | ✅ | Vuelve a `/login` |
| Registro | ❌ | Los usuarios se crean por consola (RN-01) |
| Doble factor y passkeys | ❌ | Fuera de alcance; se quitaron sus migraciones |
| Verificación de correo | ❌ | No se necesita por ahora |

Configuración: `config/fortify.php` (`features` solo con `resetPasswords()`, `home` = `/dashboard`, `redirects.logout` = `/login`).

## Piezas

| Archivo | Rol |
| --- | --- |
| `app/Providers/FortifyServiceProvider.php` | Asocia cada pantalla a su vista y define el límite de intentos (5 por minuto por correo + IP) |
| `app/Actions/Fortify/ResetUserPassword.php` | Guarda la nueva contraseña |
| `app/Actions/Fortify/PasswordValidationRules.php` | Reglas de contraseña (`Password::default()` + confirmación) |
| `resources/views/auth/*.blade.php` | Login, olvidé mi contraseña, nueva contraseña (layout `layouts::guest`) |
| `lang/es/*.php`, `lang/es.json` | Mensajes en español (errores, estados y correo de recuperación) |

## Rutas (registradas por Fortify)

| Método | URL | Nombre |
| --- | --- | --- |
| GET / POST | `/login` | `login` / `login.store` |
| POST | `/logout` | `logout` |
| GET / POST | `/forgot-password` | `password.request` / `password.email` |
| GET | `/reset-password/{token}` | `password.reset` |
| POST | `/reset-password` | `password.update` |

Las rutas del panel usan el middleware `auth`; un visitante sin sesión es redirigido a `/login`.

## Correo en local

`MAIL_MAILER=log`: el correo de recuperación no se envía, se escribe en `storage/logs/laravel.log`. Para probar: pedir el enlace en `/forgot-password`, buscar `reset-password/` en el log y abrir esa URL.

## Usuario de demostración

`php artisan db:seed` crea (o actualiza) el usuario:

- Correo: `demo@workbench.test`
- Contraseña: `password`

Solo para desarrollo local. La creación de usuarios por consola (comando artisan, F-01) llega con la etapa E1.
