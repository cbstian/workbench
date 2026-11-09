# Workbench

Sistema web para gestionar tareas, proyectos y documentación por workspace, con clientes y una wiki por workspace y por proyecto.

## Stack

- Laravel 13 y PHP 8.3 o superior
- Livewire 4
- TallStackUI 4 y Tailwind CSS 4
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
php artisan migrate
composer run dev
```

## Documentación

- Plan del sistema (flujo canónico por etapas): `docs/flujo-canonico.md`
- Requerimientos originales: `docs/insumos/`
- Permisos: `docs/permisos.md`
