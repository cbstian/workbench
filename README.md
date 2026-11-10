# Workbench

Sistema web para gestionar tareas, proyectos y documentación por workspace, con clientes y una wiki por workspace y por proyecto.

## Stack

- Laravel 13 y PHP 8.3 o superior
- Livewire 4 (componentes de clase con vista separada)
- TallStackUI 4 y Tailwind CSS 4
- Laravel Fortify (login y recuperación de contraseña)
- ApexCharts
- spatie/laravel-permission 8
- PostgreSQL
- Laravel Boost (Cursor) y Pest

## Cómo levantarlo

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build        # o `composer run dev` para desarrollo con recarga
```

En local corre en http://dev.workbench.local (nginx + PostgreSQL).

| URL | Qué hay |
| --- | --- |
| `/` | Landing |
| `/login` | Inicio de sesión |
| `/forgot-password` | Recuperar contraseña (el correo queda en `storage/logs/laravel.log`) |
| `/dashboard` | Panel: inicio |
| `/projects` | Panel: tabla de proyectos |

Usuario de demostración (creado por `php artisan db:seed`): `demo@workbench.test` / `password`.

## Calidad

```bash
php artisan test --compact     # Pest
vendor/bin/pint --format agent # estilo de código
```

## Documentación

- Plan del sistema (flujo canónico por etapas): `docs/flujo-canonico.md`
- Requerimientos originales: `docs/insumos/`
- Permisos: `docs/permisos.md`
- Arquitectura frontend (estructura, layouts, assets): `docs/arquitectura/frontend.md`
- Sistema de diseño (colores, tipografía, claro/oscuro): `docs/arquitectura/diseno.md`
- Componentes Blade (catálogo y reglas): `docs/arquitectura/componentes.md`
- Livewire (lógica y vista separadas): `docs/arquitectura/livewire.md`
- Autenticación (Fortify): `docs/arquitectura/autenticacion.md`
- Marca y logos: `docs/marca/README.md`
