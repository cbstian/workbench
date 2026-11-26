# Respaldos (spatie/laravel-backup)

El código está en GitHub. Lo que **no** está en git se respalda cada noche en un ZIP cifrado:

| Contenido | Origen | Dentro del ZIP |
| --- | --- | --- |
| Base de datos PostgreSQL | conexión `pgsql` (`pg_dump`, comprimido con gzip) | `db-dumps/postgresql-<base>.sql.gz` |
| Configuración y `APP_KEY` | `.env` | `.env` |
| Adjuntos de tareas y archivos de wiki | disco `local` | `storage/app/private/...` |
| Logos de clientes | disco `public` | `storage/app/public/...` |

Se excluye `storage/app/private/livewire-tmp` (subidas temporales de Livewire).

## Configuración

- Paquete: `spatie/laravel-backup` v10, configuración en `config/backup.php`.
- Destinos (`config/filesystems.php`):
  - `backups`: copia local en `storage/backups` (fuera de lo que se respalda, ignorada por git).
  - `backups-remote`: bucket S3 compatible (Backblaze B2, Cloudflare R2, Wasabi, AWS…) fuera del VPS.
  - Si un destino falla, se guarda igual en el otro y llega un correo de error por el que falló.
- Cifrado AES-256 con `BACKUP_ARCHIVE_PASSWORD`. Tras crear el ZIP se verifica que abra (`verify_backup`).
- Correo solo cuando algo sale mal (respaldo fallido, limpieza fallida o respaldo viejo/demasiado grande). Destinatario: `BACKUP_NOTIFICATION_EMAIL`. Requiere un `MAIL_MAILER` real en producción.
- Retención (`backup:clean`): todos los de 7 días, uno diario por 16 días, uno semanal por 8 semanas, uno mensual por 4 meses, uno anual por 2 años; tope de 5000 MB por destino.

Variables del `.env`:

```dotenv
BACKUP_NAME=workbench
BACKUP_ARCHIVE_PASSWORD=          # obligatoria; guardarla también FUERA del VPS
BACKUP_NOTIFICATION_EMAIL=
BACKUP_S3_KEY=
BACKUP_S3_SECRET=
BACKUP_S3_REGION=auto             # R2: auto · B2: p. ej. us-west-004
BACKUP_S3_BUCKET=
BACKUP_S3_ENDPOINT=               # R2: https://<cuenta>.r2.cloudflarestorage.com · B2: https://s3.<región>.backblazeb2.com
BACKUP_S3_USE_PATH_STYLE_ENDPOINT=false
DB_DUMP_BINARY_PATH=              # solo si pg_dump no está en el PATH (carpeta, sin el binario)
```

## Programación

En `routes/console.php`:

| Hora | Comando |
| --- | --- |
| 01:00 | `backup:clean` |
| 01:30 | `backup:run` |
| 03:30 | `backup:monitor` (avisa si el último respaldo tiene más de 1 día) |

En el VPS basta el cron de Laravel:

```cron
* * * * * cd /ruta/a/workbench && php artisan schedule:run >> /dev/null 2>&1
```

## Requisitos del VPS

- `pg_dump` de la **misma versión mayor o superior** que el servidor PostgreSQL (`pg_dump --version`).
- Extensión PHP `zip` con soporte de cifrado (libzip ≥ 1.2).
- Espacio libre al menos igual al tamaño de los archivos respaldados (el ZIP se arma en `storage/app/backup-temp`).

## Seguridad del bucket

- Crear una llave de acceso limitada a ese bucket.
- Activar versionado u *object lock* en el bucket si el proveedor lo permite: así, aunque alguien comprometa el VPS y use la llave, no puede borrar los respaldos anteriores.
- Guardar `BACKUP_ARCHIVE_PASSWORD`, las llaves del bucket y el `APP_KEY` en un gestor de contraseñas. **Sin la contraseña el ZIP no se puede abrir**, y el `.env` que la contiene está dentro del propio ZIP.

## Comandos útiles

```bash
php artisan backup:run                          # respaldo completo ahora
php artisan backup:run --only-to-disk=backups   # solo copia local
php artisan backup:list                         # estado de cada destino
php artisan backup:monitor                      # chequeo de salud manual
```

## Restaurar o migrar a otro servidor

1. Preparar el servidor: PHP 8.5, PostgreSQL, Composer, Node, `7z` (paquete `p7zip-full` o `7zip`).
2. Clonar el repositorio y dejar el código listo:

   ```bash
   git clone <repo> workbench && cd workbench
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   ```

3. Descargar el ZIP más reciente del bucket (carpeta `workbench/`) con la consola del proveedor, `rclone` o `aws s3 cp --endpoint-url …`.
4. Descomprimir con la contraseña (`unzip` no soporta AES-256; usar `7z`):

   ```bash
   7z x 2026-11-26-01-30-00.zip -o/tmp/restore
   ```

5. Restaurar configuración y archivos:

   ```bash
   cp /tmp/restore/.env .env                     # ajustar DB_*, APP_URL, etc. si cambió el servidor
   cp -a /tmp/restore/storage/app/private/. storage/app/private/
   cp -a /tmp/restore/storage/app/public/.  storage/app/public/
   ```

6. Restaurar la base de datos en una base **vacía**:

   ```bash
   createdb workbench
   gunzip -c /tmp/restore/db-dumps/postgresql-workbench.sql.gz | psql -d workbench
   ```

7. Terminar:

   ```bash
   php artisan storage:link
   php artisan migrate --force   # solo aplica migraciones nuevas del código, si las hay
   php artisan optimize
   ```

8. Configurar el cron del scheduler y borrar `/tmp/restore`.

Conviene ensayar esta restauración en una máquina aparte cada pocos meses: un respaldo que nunca se ha restaurado no está probado.
