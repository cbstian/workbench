# Interfaz — estructura del panel, páginas, formularios y gráficos

> Cómo se arma cualquier pantalla del panel para que los módulos nuevos se vean y se ordenen igual. Complementa [`frontend.md`](frontend.md) (árbol de archivos), [`componentes.md`](componentes.md) (catálogo) y [`diseno.md`](diseno.md) (colores y tipografía). Los agentes de IA reciben un resumen de este documento en `AGENTS.md` (fuente: `.ai/guidelines/workbench-ui.md`).

## 1. El marco (shell)

```
┌──────────────┬───────────────────────────────────────────────┐
│ Sidebar      │ Topbar: [»] Título            [tema] [avatar] │
│ (240 px)     ├───────────────────────────────────────────────┤
│ Inicio       │ <main> — ancho completo, padding lateral      │
│ Espacio…     │   <x-ui.page>                                 │
│   Proyectos  │     <x-ui.page-header>                        │
│              │     <x-ui.section> …                          │
│ ──────────── │                                               │
│ Ajustes      │                                               │
└──────────────┴───────────────────────────────────────────────┘
```

| Pieza | Archivo | Notas |
| --- | --- | --- |
| Layout | `resources/views/layouts/app.blade.php` | Sidebar + topbar + `<main>`; incluye `<x-toast />` y `<x-dialog />` una sola vez |
| Sidebar | `components/app/sidebar.blade.php` | Se dibuja desde `config/navigation.php` |
| Estado del sidebar | `resources/js/modules/sidebar.js` + `components/app/sidebar-init.blade.php` | Store Alpine `sidebar` |
| Topbar | `components/app/topbar.blade.php` | Botón de sidebar (móvil, o escritorio si está colapsado), título, tema, menú de usuario |

### Sidebar colapsable

- **Escritorio**: se oculta por completo (como Notion) con el botón « del encabezado del sidebar, el botón » de la topbar o **Ctrl/Cmd + \\**. El contenido pasa a ocupar todo el ancho.
- La preferencia se guarda en `localStorage` (`wb-sidebar`) y se refleja en `<html data-sidebar="collapsed|expanded">`. `<x-app.sidebar-init />` la aplica **antes del primer pintado**, igual que el tema, para que no salte.
- En CSS hay dos variantes: `sidebar-collapsed:` y `sidebar-expanded:` (definidas en `app.css`). Ej.: `md:sidebar-expanded:pl-60`.
- **Móvil**: panel off-canvas sobre un fondo oscuro (`$store.sidebar.mobileOpen`); se cierra con Escape, tocando fuera o al navegar.

### Navegación: `config/navigation.php`

Fuente única del menú. **Un módulo nuevo agrega su entrada aquí; no se edita el sidebar.**

```php
'groups' => [
    ['label' => 'Espacio de trabajo', 'items' => [
        ['label' => 'Clientes', 'route' => 'clients.index', 'icon' => 'building-office', 'active' => 'clients.*', 'can' => 'viewAny-client'],
    ]],
],
'footer' => [ /* ítems fijos abajo (Ajustes) */ ],
```

- `active`: patrón (o lista) de nombres de ruta que marcan el ítem; por defecto, la misma `route`.
- `can`: habilidad opcional; si el usuario no la tiene, el ítem no aparece. Un grupo sin ítems visibles no muestra su título.
- `children`: nombre de un componente Livewire que dibuja la sublista del ítem (ej. `navigation.project-links` lista cada proyecto bajo «Proyectos»). El ítem muestra un chevron para expandir/contraer.

### Grupos e ítems contraíbles

- Un grupo con título se contrae haciendo clic en su título (el chevron aparece al pasar el mouse), como en Notion.
- Un ítem con `children` se contrae con su chevron; la sublista va indentada con una guía vertical.
- El estado abierto/cerrado se recuerda por navegador con `$persist` de Alpine (claves `wb-nav-group-<título>` y `wb-nav-item-<ruta>`).
- Las sublistas son componentes Livewire en `app/Livewire/Navigation/` y se refrescan con un evento. Ej.: tras crear o renombrar un proyecto, `$this->dispatch('projects-changed')` actualiza el sidebar sin recargar.
- `ProjectLinks` muestra hasta 30 proyectos ordenados por nombre y luego «Ver todos». Cuando existan permisos por proyecto, la consulta debe filtrar por los proyectos del usuario.
- Un ítem cuya ruta aún no existe se omite (útil mientras se construye un módulo).

### Espacio de trabajo actual

- El usuario trabaja en un Workspace a la vez (F-02). `App\Support\CurrentWorkspace` lo guarda en la sesión y, si falta o ya no es visible, toma el primero visible (los propios primero).
- Se cambia desde el selector del sidebar (`navigation.workspace-switcher`), que se refresca con el evento `workspaces-changed`.
- Las páginas dentro de un Workspace usan el middleware `EnsureCurrentWorkspace` y el trait `App\Livewire\Concerns\WithCurrentWorkspace` (`$this->workspace`).

### Tareas de un proyecto (E7)

- `App\Livewire\Projects\TaskBoard` (dentro de la página del proyecto) muestra las mismas tareas raíz filtradas en **kanban**, **listado** (ordenable) y **calendario** (mes, lunes primero; las tareas sin fechas no aparecen).
- Filtros: estado, prioridad, asignado, etiqueta, rango de fechas (solapamiento con inicio–término) y búsqueda (full-text `es_unaccent` + `ilike` en el nombre). Viven en `App\Support\TaskFilters`, que descarta ids que ya no son del proyecto.
- La vista y los filtros se guardan por usuario y proyecto en `project_task_views` en cada cambio; el mes del calendario y el orden del listado no se recuerdan.
- Kanban: arrastrar con `wire:sort` (grupo `tasks`, id de grupo = estado). `App\Actions\Tasks\MoveTask` cambia estado y posición (huecos de 1024, rebalanceo si los vecinos son contiguos) y registra `status_changed`. Si el usuario no puede editar la tarea, se avisa y no se mueve.

### Pizarra (E11)

- `App\Livewire\Board\Index` (`/pizarra`, junto a Inicio, habilidad `view-projects`) es la pizarra del Workspace: filas de Proyectos y columnas de la semana (lunes a viernes; «Fin de semana» suma sábado y domingo). Pensada para un monitor vertical: columna de Proyecto fija, días en un contenedor con scroll, columna de hoy con `bg-today`.
- La semana (`semana`) y los filtros (`fin_de_semana`, `sin_pausados`, `vencidos`, `sin_trabajo`, `q`) van en la URL. La página se refresca con `wire:poll.visible` cada 45 segundos.
- Notas, prioridad, pausa y orden de filas son compartidos (ver `docs/permisos.md`). Ocultar un Proyecto es personal. Las subfilas son las Tareas raíz abiertas y se pliegan en el navegador (`$persist`).
- Una celda vacía (o el «+») abre una nota; Enter la guarda. «Ya hecho» la crea cerrada. Clic en una nota: marcar hecha, editar, vincular una Tarea del Proyecto o eliminar.

### Wiki (E8)

- `App\Livewire\Wiki\Index` sirve la wiki del Workspace y la de cada Proyecto (árbol, búsqueda full-text, filtro por etiqueta y «Nueva página»); `Wiki\PageShow` muestra, edita y mueve una Página, con subpáginas, archivos (`Wiki\PageFiles`) e historial.
- El contenido se escribe con `<x-editor markdown>` de TallStackUI y se guarda como Markdown. Se muestra con `Page::bodyHtml()` (`Str::markdown` con HTML escapado y sin enlaces inseguros) dentro de `.wb-prose` (`resources/css/components/prose.css`).
- El historial registra título, icono y visibilidad (`updated`), el cuerpo solo con su largo anterior y nuevo (`body_updated`, sin versiones), el padre (`moved`), etiquetas y archivos.
- Las etiquetas de wiki son del Workspace; se crean desde el editor de la Página.

## 2. Anatomía de una página

```blade
<x-ui.page width="full">                       {{-- full | wide | narrow --}}
    <x-ui.page-header title="Clientes" description="…" icon="🏢"
                      :breadcrumbs="[['label' => 'Inicio', 'link' => route('dashboard')], ['label' => 'Clientes']]">
        <x-slot:actions>
            <x-button icon="plus" text="Nuevo cliente" sm wire:click="create" />
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.section title="Activos">
        <x-table … />                           {{-- o <x-ui.empty-state> si no hay datos --}}
    </x-ui.section>
</x-ui.page>
```

| Ancho | Clase | Cuándo |
| --- | --- | --- |
| `full` (por defecto) | todo el ancho disponible | Listados, tablas, kanban, calendario, dashboards, detalle de un registro y wiki |
| `wide` | `max-w-7xl` centrado | Configuración con varias secciones de formulario (p. ej. configurar un proyecto) |
| `narrow` | `max-w-3xl` centrado | Solo un formulario corto (p. ej. Ajustes del perfil) |

Reglas:

1. La raíz de toda vista de página es `<x-ui.page>` (el componente Livewire necesita un solo elemento raíz; `x-ui.page` lo es).
2. Un solo `<x-ui.page-header>` por página. La acción principal va última en `actions`.
3. Bloques dentro de la página con `<x-ui.section>` (título chico y gris, sin caja). Si el contenido necesita superficie, envolverlo en `<x-card>` de TallStackUI (ya personalizado: borde fino, sin sombra).
4. Listas vacías con `<x-ui.empty-state>`; nunca una tabla vacía sin explicación.
5. Rejillas de cifras o gráficos: `grid gap-3 sm:grid-cols-3` (cifras) y `grid gap-6 lg:grid-cols-3` con `lg:col-span-2` (gráfico principal + secundario).

## 3. Formularios

Página de referencia: **Ajustes** (`App\Livewire\Settings\Profile`, `/settings`).

### Lógica

- Campos, reglas, nombres de atributos en español y guardado en un **Form object** de Livewire: `php artisan livewire:form ClientForm` → `app/Livewire/Forms/ClientForm.php`. Ver `ProfileForm` y `PasswordForm`.
- El componente de página solo orquesta: llama a `$this->form->store()` / `update()` y avisa con el trait `TallStackUi\Traits\Interactions`:

```php
$this->toast()->success('Cliente creado', 'Ya puedes asignarle proyectos.')->send();
```

- Confirmaciones destructivas con `$this->dialog()->question(...)->confirm(...)->cancel()->send()`.

### Diseño

```blade
<form wire:submit="save">
    <x-ui.form-section title="Datos del cliente" description="Se muestran en los proyectos asociados.">
        <x-ui.form-grid cols="2">
            <x-input label="Nombre *" wire:model="form.name" />
            <x-color label="Color" wire:model="form.color" />
            <div class="sm:col-span-2">
                <x-textarea label="Descripción" wire:model="form.description" />
            </div>
        </x-ui.form-grid>

        <x-slot:actions>
            <x-ui.form-actions>
                <x-button flat color="secondary" text="Cancelar" sm />
                <x-button type="submit" text="Guardar" sm loading="save" />
            </x-ui.form-actions>
        </x-slot:actions>
    </x-ui.form-section>
</form>
```

- `<x-ui.form-section>`: título y descripción a la izquierda, campos a la derecha (apilado en móvil). Varias secciones seguidas = página de ajustes; cada una puede ser su propio `<form>`.
- `<x-ui.form-grid cols="1|2|3">`: rejilla responsiva. Para un campo más ancho, envolverlo en `<div class="sm:col-span-2">` (la clase en el campo de TallStackUI va al `<input>`, no al contenedor).
- `<x-ui.form-actions>`: botones a la derecha, el de enviar al final con `loading="método"`; slot `status` para un mensaje a la izquierda.
- Campos: siempre de TallStackUI (`x-input`, `x-password`, `x-textarea`, `x-select.styled`, `x-toggle`, `x-checkbox`, `x-date`, `x-color`, `x-upload`…). Revisar su página en `vendor/tallstackui/tallstackui/.ai/components/` antes de usar un prop.
- Obligatorios: la etiqueta termina en ` *`.
- **Dónde abrir un formulario**: crear/editar algo corto desde un listado → `<x-slide>` (panel lateral) en el mismo componente; formulario largo o con varias secciones → página propia con `width="narrow"`.

## 4. Gráficos (ApexCharts)

- Componente: `<x-ui.chart :options="$this->projectsPerMonthChart" height="260" />`.
- Las opciones se arman en la clase Livewire con un método `#[Computed]` que devuelve el arreglo de [opciones de ApexCharts](https://apexcharts.com/docs/options/). Solo se declara lo propio del gráfico (`chart.type`, `series`, `labels`/`xaxis`, `yaxis`); colores, fuente, grilla, tooltip y modo claro/oscuro los pone `resources/js/modules/chart.js`.
- ApexCharts se carga con `import()` dinámico: las páginas sin gráficos no lo descargan.
- Al cambiar el tema, el gráfico se actualiza solo. Si Livewire vuelve a renderizar con datos nuevos, el gráfico se recrea (el componente se identifica por sus opciones).
- Colores de series: tokens `--wb-chart-1` … `--wb-chart-6` (ver `diseno.md`).
- Ejemplos: `App\Livewire\Dashboard::projectsPerMonthChart()` (área) y `visibilityChart()` (donut).
- No usar el `<x-chart>` de TallStackUI: el motor de gráficos del proyecto es ApexCharts.

## 5. Checklist para un módulo nuevo

1. `php artisan make:livewire Clients/Index` (clase + vista + test).
2. Ruta en `routes/web.php` dentro del grupo `auth`, con `Route::livewire()` y nombre en inglés (`clients.index`).
3. `#[Title('Clientes')]` en la clase.
4. Entrada en `config/navigation.php` (con `can` si aplica).
5. Vista con `<x-ui.page>` → `<x-ui.page-header>` → `<x-ui.section>`.
6. Formularios con Form object + `<x-ui.form-section>` / `<x-ui.form-grid>` / `<x-ui.form-actions>` y toast al guardar.
7. Solo tokens semánticos; nada de colores fijos ni `dark:`.
8. Tests: acceso de invitados, render, validaciones y acciones.

## 6. Herramientas de IA en el editor

| Herramienta | Qué aporta | Dónde está configurada |
| --- | --- | --- |
| Laravel Boost (MCP + guías + skills) | Docs de Laravel/Livewire/TallStackUI, esquema de BD, logs | `boost.json`, `AGENTS.md`, `.mcp.json`, `.grok/config.toml`, `.kilo/kilo.jsonc`, `.cursor/mcp.json` |
| MCP de TallStackUI | Documentación de componentes y clases | `https://tallstackui.com/mcp/tallstackui` en los mismos archivos |
| MCP de ApexCharts (`apexcharts-mcp`) | Genera y valida opciones de gráficos | `npx -y apexcharts-mcp` en los mismos archivos |
| Skill de ApexCharts | Referencia de ApexCharts v8 para el agente | `.ai/skills/apexcharts/` (copia de [apexcharts-skill](https://github.com/apexcharts/apexcharts-skill)) |
| Guía de interfaz | Este documento resumido | `.ai/guidelines/workbench-ui.md` |

- `.ai/` es la fuente: `php artisan boost:update` copia skills y guías a `.claude/skills`, `.cursor/skills`, `.grok/skills` y `AGENTS.md`. No editar las copias.
- Para actualizar la skill de ApexCharts: volver a copiar `SKILL.md`, `references/` y `LICENSE` del repositorio oficial a `.ai/skills/apexcharts/` y correr `php artisan boost:update`. (`boost:add-skill` no sirve con ese repositorio porque su `SKILL.md` está en la raíz.)
- VS Code / Copilot: crear `.vscode/mcp.json` local (la carpeta está en `.gitignore`) con `{"servers": {"apexcharts": {"type": "http", "url": "https://mcp.apexcharts.com/mcp"}, "tallstackui": {"type": "http", "url": "https://tallstackui.com/mcp/tallstackui"}}}`.
