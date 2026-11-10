# Frontend — estructura y convenciones

> Cómo está ordenado el frontend de Workbench y dónde va cada cosa. Código en inglés, interfaz y documentación en español (RN-20).

## Stack

| Pieza | Versión | Rol |
| --- | --- | --- |
| Laravel | 13 | Rutas, Blade, Vite, fuentes (`@fonts`) |
| Livewire | 4 | Componentes interactivos y páginas del panel (clase + vista separadas) |
| TallStackUI | 4 | Kit de componentes (inputs, botones, dropdown, tabla, theme switch…) |
| Tailwind CSS | 4 | Utilidades; tema definido en CSS (`@theme`) |
| Alpine.js | (incluido en Livewire) | Interacciones de cliente pequeñas |
| Laravel Fortify | 1.x | Backend de autenticación (sin vistas propias) |

## Árbol de `resources/`

```
resources/
├── css/
│   ├── app.css                  # Punto de entrada: importa todo en orden
│   ├── theme/
│   │   ├── palette.css          # Escalas de color y familias tipográficas (@theme)
│   │   └── tokens.css           # Tokens semánticos claro/oscuro (canvas, ink, line…)
│   ├── base/
│   │   └── base.css             # Estilos globales de elementos (html, body, foco, selección)
│   └── components/
│       └── landing.css          # CSS que no cabe en utilidades (animación de la landing)
├── js/
│   └── app.js                   # Punto de entrada JS (ApexCharts + imágenes para Vite)
├── images/
│   └── brand/                   # Logos (ver docs/marca/README.md)
│       └── originales/          # JPG entregados, intactos
└── views/
    ├── layouts/                 # Layouts de página (namespace `layouts::`)
    │   ├── base.blade.php       # Documento HTML común
    │   ├── guest.blade.php      # Pantallas sin sesión (login, recuperación)
    │   └── app.blade.php        # Panel: sidebar + topbar
    ├── components/              # Componentes Blade anónimos (`<x-…>`), por carpeta
    │   ├── app/                 # Piezas del panel (sidebar, topbar, menú de usuario…)
    │   ├── auth/                # Piezas de las pantallas de autenticación
    │   ├── brand/               # Isotipo, wordmark y logo
    │   ├── meta/                # <head>: favicons y metadatos
    │   ├── theme/               # Tema claro/oscuro
    │   └── ui/                  # Piezas genéricas reutilizables (page-header, stat…)
    ├── livewire/                # Vistas de componentes Livewire (espejo de app/Livewire)
    ├── auth/                    # Vistas que Fortify muestra (login, forgot, reset)
    └── landing.blade.php        # Página pública
```

Catálogo de componentes y cómo crear nuevos: [`componentes.md`](componentes.md). Colores, tipografía y tema: [`diseno.md`](diseno.md). Livewire: [`livewire.md`](livewire.md).

## Layouts

Los layouts son componentes anónimos registrados por Livewire bajo el namespace `layouts` (`config/livewire.php` → `component_namespaces`):

| Layout | Uso | Cómo se usa |
| --- | --- | --- |
| `layouts::base` | Documento HTML: `<head>`, tema, fuentes, TallStackUI, Livewire y Vite | Lo usan los otros layouts y la landing |
| `layouts::guest` | Login y recuperación de contraseña | `<x-layouts::guest title="…">` |
| `layouts::app` | Panel autenticado | Automático para páginas Livewire (`component_layout`); en Blade, `<x-layouts::app title="…">` |

Reglas:

- El título de la pestaña se arma como `Título · Workbench`. En páginas Livewire se define con `#[Title('…')]` en la clase.
- Un layout nuevo va en `resources/views/layouts/` y debe envolver a `layouts::base`.

## Assets

- **CSS**: un solo punto de entrada (`resources/css/app.css`). Los archivos parciales se importan desde ahí; no se agregan entradas nuevas a Vite salvo que una página lo justifique.
- **JS**: `resources/js/app.js`. Alpine viene con Livewire y TallStackUI se carga con `<tallstackui:script />`; no importarlos de nuevo.
- **Imágenes**: en `resources/images/`. Vite las versiona gracias a `import.meta.glob` en `app.js`; en Blade se referencian con `Vite::asset('resources/images/…')`. Los favicons viven en `public/` porque necesitan URL fija.
- **Fuentes**: Inter (interfaz) y Space Grotesk (marca) se descargan de Bunny Fonts **al compilar** y se sirven desde `public/build` (sin peticiones externas en producción). Se configuran en `vite.config.js` y se cargan con `@fonts` en `layouts::base`.

## Comandos

```bash
npm run dev       # desarrollo con recarga (o composer run dev)
npm run build     # compilación para producción (necesaria si no corre `npm run dev`)
```

## Pendiente / a considerar

- `app.js` importa ApexCharts completo (~950 KB sin comprimir). Cuando se use un gráfico, conviene cargarlo con `import()` dinámico solo en las páginas que lo necesitan.
- `app.css` escanea todo `vendor/tallstackui` (`@source`), lo que infla el CSS (~430 KB, 51 KB gzip). Se puede acotar a los componentes usados cuando el set se estabilice.
