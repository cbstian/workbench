# Componentes

> Catálogo de componentes Blade propios y reglas para crear nuevos. Los componentes de TallStackUI (`<x-input>`, `<x-button>`, `<x-table>`…) se usan directo y están documentados en https://tallstackui.com/docs/v4.

## Tipos de componente y dónde van

| Tipo | Ubicación | Cuándo usarlo |
| --- | --- | --- |
| Componente Blade anónimo | `resources/views/components/<grupo>/<nombre>.blade.php` | Presentación pura, sin lógica (o lógica mínima de vista) |
| Componente Blade con clase | `app/View/Components/…` + vista | Presentación que necesita preparar datos (aún no hay ninguno) |
| Componente Livewire | `app/Livewire/…` + `resources/views/livewire/…` | Estado en servidor, acciones, búsqueda, formularios (ver [`livewire.md`](livewire.md)) |
| Layout | `resources/views/layouts/` | Esqueleto de una página completa |

Reglas:

1. **Agrupar por dominio** en carpetas (`app/`, `auth/`, `brand/`, `ui/`…). El nombre del archivo es el del componente en kebab-case: `components/ui/page-header.blade.php` → `<x-ui.page-header>`.
2. **Documentar en la cabecera** de cada componente, con un comentario Blade `{{-- --}}`: qué hace, props y un ejemplo si no es obvio.
3. **Declarar props con `@props`** y aceptar clases extra con `$attributes->class(...)`.
4. **Usar tokens semánticos** (`text-ink`, `bg-surface`, `border-line`…) en vez de colores fijos.
5. **Reutilizar TallStackUI** antes de crear algo nuevo; crear un componente propio solo si el kit no lo cubre o si se repite una composición.
6. Textos visibles en español; nombres de archivos, props y variables en inglés.

## Catálogo

### Marca (`components/brand`)

| Componente | Descripción | Ejemplo |
| --- | --- | --- |
| `<x-brand.mark>` | Isotipo en SVG en línea; colores desde `--wb-brand-*`, cambia con el tema | `<x-brand.mark class="size-6" />` |
| `<x-brand.wordmark>` | "workbench" en Space Grotesk | `<x-brand.wordmark class="text-7xl" />` |
| `<x-brand.logo>` | Isotipo + wordmark. **Usar este en la interfaz** | `<x-brand.logo mark-class="size-5" />` |

### Tema y metadatos (`components/theme`, `components/meta`)

| Componente | Descripción |
| --- | --- |
| `<x-theme.init>` | Script en `<head>` que aplica el tema antes de pintar. Solo en `layouts::base` |
| `<x-theme.toggle>` | Botón de ícono para alternar claro/oscuro |
| `<x-meta.favicons>` | Favicons, `apple-touch-icon`, manifest y `theme-color` |

### Panel (`components/app`)

| Componente | Descripción |
| --- | --- |
| `<x-app.sidebar>` | Barra lateral colapsable (escritorio) y off-canvas (móvil), dibujada desde `config/navigation.php` |
| `<x-app.sidebar-init>` | Script en `<head>` que aplica el estado colapsado antes de pintar. Solo en `layouts::app` |
| `<x-app.nav-group>` | Grupo de ítems con título opcional; filtra por ruta existente y `can`. Props: `label`, `items` |
| `<x-app.nav-item>` | Ítem de navegación. Props: `href`, `icon` (Heroicons), `active` |
| `<x-app.topbar>` | Barra superior: botón de sidebar, título, tema y menú de usuario. Prop: `title` |
| `<x-app.user-menu>` | Dropdown con nombre/correo, Ajustes, selector de tema y cerrar sesión |
| `<x-app.avatar>` | Avatar de iniciales. Prop: `name` |

Agregar una sección al menú: **no se edita el sidebar**, se agrega una entrada en `config/navigation.php` (ver [`interfaz.md`](interfaz.md#navegación-confignavigationphp)).

### Autenticación (`components/auth`)

| Componente | Descripción |
| --- | --- |
| `<x-auth.heading>` | Título y bajada de cada pantalla. Props: `title`, `description` |
| `<x-auth.status>` | Mensaje de estado de Fortify (`session('status')`) |

### Genéricos (`components/ui`)

| Componente | Descripción | Ejemplo |
| --- | --- | --- |
| `<x-ui.page>` | Raíz de toda página: ancho (`full`, `wide`, `narrow`) y ritmo vertical | `<x-ui.page width="narrow">…</x-ui.page>` |
| `<x-ui.page-header>` | Título grande, `description`, `icon` (emoji), `breadcrumbs` y slot `actions` | `<x-ui.page-header title="Proyectos" description="…"><x-slot:actions>…</x-slot:actions></x-ui.page-header>` |
| `<x-ui.section>` | Bloque con título chico, `description` y slot `actions` | `<x-ui.section title="Actividad reciente">…</x-ui.section>` |
| `<x-ui.empty-state>` | Estado vacío. Props: `icon`, `title`, `description`; slot para la acción | `<x-ui.empty-state title="Aún no hay proyectos" />` |
| `<x-ui.stat>` | Tarjeta de cifra. Props: `label`, `value`, `hint` | `<x-ui.stat label="Proyectos" :value="36" />` |
| `<x-ui.chart>` | Gráfico de ApexCharts con los colores del tema. Props: `options`, `height` | `<x-ui.chart :options="$this->visibilityChart" height="260" />` |

### Formularios (`components/ui/form-*`)

| Componente | Descripción |
| --- | --- |
| `<x-ui.form-section>` | Título y descripción a la izquierda, campos a la derecha. Slot `actions` |
| `<x-ui.form-grid>` | Rejilla responsiva de campos. Prop: `cols` (1, 2, 3) |
| `<x-ui.form-actions>` | Fila de botones alineada a la derecha; slot `status` |

Uso completo y reglas en [`interfaz.md`](interfaz.md#3-formularios).

## Componentes de TallStackUI en uso

`x-input`, `x-password`, `x-checkbox`, `x-button`, `x-dropdown` / `x-dropdown.items`, `x-theme-switch`, `x-table`, `x-badge`, `x-icon`, `x-card`, `x-kbd`, `x-toast`, `x-dialog`.
