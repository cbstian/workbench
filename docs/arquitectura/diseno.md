# Sistema de diseño

> Colores, tipografía y modo claro/oscuro. La fuente de verdad son los archivos de `resources/css/theme/`.

## Principios

- **Sobrio y legible**, inspirado en Notion: fondos neutros cálidos, texto casi negro, bordes finos, poco color.
- **Un solo acento**: el roble/nogal del logo (`primary`). Se usa en acciones principales, foco y estados activos.
- **Sin `dark:` en las vistas de la aplicación**: los colores semánticos cambian solos con el tema. `dark:` queda para casos puntuales.

## Capas de color

### 1. Escalas (`resources/css/theme/palette.css`)

Definidas con `@theme`, reemplazan escalas de Tailwind y de TallStackUI. Así todos los componentes del kit toman la paleta sin personalizarlos uno por uno.

| Escala | Qué es | Dónde se nota |
| --- | --- | --- |
| `primary` | Roble → nogal (de `#faf7f3` a `#241a13`, base `primary-500 #8f6b4a`) | Botones, foco de inputs, paginación activa, badges "Visible" |
| `secondary` | Neutro cálido | Acciones de bajo énfasis, badges "Oculto" |
| `gray` | Grises cálidos tipo Notion (reemplaza el `gray` de Tailwind) | TallStackUI en modo claro |
| `dark` | Casi negros neutros (`dark-900 #191918`) | TallStackUI en modo oscuro |

### 2. Tokens semánticos (`resources/css/theme/tokens.css`)

Variables CSS con valor claro (`:root`) y oscuro (`.dark`), expuestas como utilidades de Tailwind con `@theme inline`:

| Token / utilidad | Claro | Oscuro | Uso |
| --- | --- | --- | --- |
| `canvas` (`bg-canvas`) | `#ffffff` | `#191918` | Fondo de página |
| `sidebar` | `#f7f7f5` | `#202020` | Barra lateral |
| `surface` | `#ffffff` | `#252524` | Tarjetas, paneles |
| `surface-muted` | `#fbfbfa` | `#1f1f1e` | Avisos, fondos secundarios |
| `ink` (`text-ink`) | `#37352f` | `#e6e5e3` | Texto principal |
| `ink-muted` | `#787774` | `#9b9a97` | Texto secundario |
| `ink-subtle` | `#a5a29c` | `#6b6a66` | Metadatos, etiquetas de sección |
| `line` (`border-line`) | `#edece9` | `#2f2f2d` | Bordes y divisores |
| `line-strong` | `#e0deda` | `#3f3e3b` | Bordes con más contraste |
| `hover` / `active` | negro 6 % / 9 % | blanco 5,5 % / 8,5 % | Fondos de hover y de ítem activo |
| `accent` | `primary-500` | `primary-400` | Acento puntual |
| `ring` | roble 35 % | roble 40 % | Foco y selección de texto |
| `overlay` (`bg-overlay`) | negro cálido 45 % | negro 62 % | Velo detrás de paneles laterales y modales |
| `elevated` (`bg-elevated`) | `#ffffff` | `#2b2b29` | Fondo de paneles que se superponen a la página |
| `edge` (`ring-edge`) | `#d3d0ca` | `#4a4945` | Borde visible de esos paneles |
| `--wb-scrollbar-thumb` / `-hover` | negro 18 % / 32 % | blanco 14 % / 26 % | Barra de scroll sutil (utilidad `scrollbar-subtle`, sin clase de Tailwind propia) |

Además: `--wb-brand-*` (colores del isotipo), `--wb-glow-*`, `--wb-grid-dot` (fondo de la landing) y `--wb-chart-1` … `--wb-chart-6` (series de gráficos, en orden: roble, azul, verde, naranja, morado, rosa; versión clara y oscura). Los lee `resources/js/modules/chart.js`, no hay utilidad de Tailwind para ellos.

**Agregar un color**: primero preguntarse si es un rol nuevo (token) o un tono nuevo (escala). Un rol nuevo se agrega en `tokens.css` con ambos valores y su línea en `@theme inline`; luego se documenta en esta tabla.

## Tipografía

| Familia | Uso | Utilidad | Pesos |
| --- | --- | --- | --- |
| **Inter** | Toda la interfaz | `font-sans` (por defecto) | 400, 500, 600, 700 |
| **Space Grotesk** | Marca (wordmark, cifras destacadas) | `font-display` | 500, 600, 700 |

- Ambas se autohospedan al compilar (ver `vite.config.js`). Las variables `--font-inter` y `--font-space-grotesk` las entrega `@fonts`.
- Inter usa los rasgos `cv11` y `ss01` (a de un piso y números más claros), definidos en `base.css`.
- Escala usada: títulos de página `text-3xl font-bold tracking-tight`, títulos de sección `text-sm font-medium text-ink-muted`, cuerpo `text-sm`, metadatos `text-xs text-ink-subtle`.

## Modo claro / oscuro

- La clase `dark` vive en `<html>`. TallStackUI ya define la variante `dark` sobre esa clase.
- El estado lo maneja el store de TallStackUI `tallstackui_darkTheme({ default: 'system' })` (en `layouts::base`): guarda `light`, `dark` o `system` en `localStorage` (clave `dark-theme`) y escucha los cambios del sistema operativo.
- `<x-theme.init />` aplica el tema **antes del primer pintado** para evitar el destello blanco.
- Controles: `<x-theme.toggle />` (ícono claro/oscuro en la barra superior y en las pantallas de acceso) y el selector de tres opciones (oscuro / sistema / claro) en el menú de usuario.
- Sin preferencia guardada se respeta la del sistema.

## Personalización de TallStackUI

- Colores: por las escalas de `palette.css` (no hace falta tocar componentes).
- Íconos: Heroicons en estilo `outline` (`config/tallstackui.php`).
- Ajustes de clases que los tokens no alcanzan: `App\Providers\TallStackUiServiceProvider` (soft customization). Hoy: tarjetas planas (`x-card` con borde fino y sin sombra), paneles laterales (`x-slide`: velo `overlay` con leve desenfoque, panel `elevated` con borde `edge` y sombra marcada) y colores neutros del theme switch. Toda personalización nueva del kit va en ese provider, con un método por componente.

## Movimiento

- Animaciones solo con `transform` y `opacity`, lentas y discretas.
- Siempre respetar `prefers-reduced-motion` (ver `resources/css/components/landing.css`).
