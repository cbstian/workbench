# Livewire — lógica y vista separadas

> Convención del proyecto: **la clase PHP tiene la lógica y la vista Blade tiene el diseño**. No se usan componentes de archivo único (SFC) ni multiarchivo con lógica en la vista (MFC), aunque Livewire 4 los ofrece por defecto.

## Configuración

En `config/livewire.php`:

```php
'make_command' => [
    'type' => 'class',   // clase en app/Livewire + vista en resources/views/livewire
    'emoji' => false,    // sin ⚡ en nombres de archivo
    'with' => ['test' => true], // genera además un test de Pest
],
'component_layout' => 'layouts::app', // layout de las páginas completas
```

## Crear un componente

```bash
php artisan make:livewire Clients/Index --no-interaction
```

Genera:

| Archivo | Contenido |
| --- | --- |
| `app/Livewire/Clients/Index.php` | Clase: estado, validación, autorización, consultas, acciones |
| `resources/views/livewire/clients/index.blade.php` | Vista: solo marcado y componentes |
| `tests/Feature/Livewire/Clients/IndexTest.php` | Test de Pest |

## Reglas

1. **La vista no consulta la base de datos ni decide reglas de negocio.** Recibe datos ya preparados.
2. **Datos derivados como `#[Computed]`** en la clase y se leen en la vista con `$this->…` (ej. `$this->rows`, `$this->stats`). Se calculan una vez por request.
3. **`render(): View`** devuelve siempre la vista explícita (`view('livewire.…')`), con tipo de retorno declarado (el stub de Livewire no lo trae; agregarlo a mano).
4. **Páginas completas** se registran con `Route::livewire()` y se titulan con `#[Title('…')]`; usan `layouts::app`.
5. **Entrada del usuario siempre validada**: valores que terminan en SQL (orden, tamaño de página) se comparan contra una lista permitida (ver `Projects\Index::SORTABLE_COLUMNS` y `PAGE_SIZES`).
6. **Estado en la URL** solo cuando aporta (búsqueda compartible con `#[Url]`).
7. **Lógica reutilizable fuera del componente**: scopes del modelo (ej. `Project::search()`), acciones o servicios. El componente orquesta.
8. **Formularios en Form objects** (`app/Livewire/Forms/`, `php artisan livewire:form`): campos, reglas y guardado; el componente solo orquesta y avisa con toast. Ver [`interfaz.md`](interfaz.md#3-formularios).
9. Los componentes se agrupan por dominio, igual que las vistas: `app/Livewire/Projects/Index.php` ↔ `resources/views/livewire/projects/index.blade.php`.

## Componentes actuales

| Clase | Ruta | Qué hace |
| --- | --- | --- |
| `App\Livewire\Dashboard` | `GET /dashboard` (`dashboard`) | Saludo, cifras, gráficos de proyectos (ApexCharts) y actividad reciente |
| `App\Livewire\Projects\Index` | `GET /projects` (`projects.index`) | Tabla de proyectos con búsqueda, orden y paginación (`x-table` de TallStackUI) |
| `App\Livewire\Settings\Profile` | `GET /settings` (`settings.profile`) | Perfil y contraseña (Form objects `ProfileForm`, `PasswordForm`); página de referencia de formularios |
