# Sistema de diseño

> Colores, paletas, tipografía y modo claro/oscuro. La fuente de verdad son los archivos de `resources/css/theme/`.

## Principios

- **Sobrio y legible**, inspirado en Notion: fondos neutros cálidos, texto casi negro, bordes finos, poco color.
- **Un solo acento por paleta** (`primary`): roble/nogal en la paleta por defecto. Se usa en acciones principales, foco y estados activos.
- **Sin `dark:` en las vistas de la aplicación**: los colores semánticos cambian solos con el tema. `dark:` queda para casos puntuales.

## Capas de color

### 1. Escalas (`resources/css/theme/scales.css`)

Definidas con `@theme inline`, reemplazan escalas de Tailwind y de TallStackUI. Así todos los componentes del kit toman la paleta sin personalizarlos uno por uno. No tienen valores propios: cada `--color-{escala}-{tono}` apunta a `--wb-{escala}-{tono}`, que define la paleta activa (ver «Paletas»).

| Escala | Qué es | Dónde se nota |
| --- | --- | --- |
| `primary` | Acento de la paleta (en Roble: `#faf7f3` → `#241a13`, base `primary-500 #8f6b4a`) | Botones, foco de inputs, paginación activa, badges "Visible" |
| `secondary` | Neutro de la paleta | Acciones de bajo énfasis, badges "Oculto" |
| `gray` | Neutros claros (reemplaza el `gray` de Tailwind) | TallStackUI en modo claro |
| `dark` | Neutros oscuros | TallStackUI en modo oscuro |

### 2. Tokens semánticos (`resources/css/theme/tokens.css`)

Variables CSS expuestas como utilidades de Tailwind con `@theme inline`. Cada una tiene valor claro y oscuro y viene de uno de tres orígenes: **paleta** (la define cada archivo de `palettes/`), **derivado** (se calcula en `tokens.css` con `color-mix()` a partir de la paleta) o **compartido** (igual en todas las paletas, en `tokens.css`; una paleta puede sobrescribirlo). Los valores de la tabla son los de Roble.

| Token / utilidad | Claro | Oscuro | Uso |
| --- | --- | --- | --- |
| `canvas` (`bg-canvas`) · paleta | `#ffffff` | `#191918` | Fondo de página |
| `sidebar` · paleta | `#f7f7f5` | `#202020` | Barra lateral |
| `surface` · paleta | `#ffffff` | `#252524` | Tarjetas, paneles |
| `surface-muted` · paleta | `#fbfbfa` | `#1f1f1e` | Avisos, fondos secundarios |
| `ink` (`text-ink`) · paleta | `#37352f` | `#e6e5e3` | Texto principal |
| `ink-muted` · paleta | `#787774` | `#9b9a97` | Texto secundario |
| `ink-subtle` · paleta | `#a5a29c` | `#6b6a66` | Metadatos, etiquetas de sección |
| `line` (`border-line`) · paleta | `#edece9` | `#2f2f2d` | Bordes y divisores |
| `line-strong` · paleta | `#e0deda` | `#3f3e3b` | Bordes con más contraste |
| `hover` / `active` · derivado | `ink` al 6 % / 9 % | `ink` al 6 % / 9 % | Fondos de hover y de ítem activo |
| `accent` · derivado | `primary-500` | `primary-400` | Acento puntual |
| `ring` · derivado | `primary-500` al 35 % | `primary-400` al 40 % | Foco y selección de texto |
| `overlay` (`bg-overlay`) · compartido | negro cálido 45 % | negro 62 % | Velo detrás de paneles laterales, diálogos y modales |
| `elevated` (`bg-elevated`) · paleta | `#ffffff` | `#2b2b29` | Fondo de paneles que se superponen a la página |
| `edge` (`ring-edge`) · paleta | `#d3d0ca` | `#4a4945` | Borde visible de esos paneles |
| `priority-none` … `priority-urgent` · compartido | gris, azul `#337ea9`, oro `#c2922a`, naranja `#d9730d`, rojo `#c4554d` | gris, azul `#529cca`, oro `#e0b14a`, naranja `#ffa344`, rojo `#e06a62` | Punto de prioridad del Proyecto en la Pizarra (ninguna, baja, media, alta, urgente) |
| `today` (`bg-today`) · derivado | `primary-500` al 14 % | `primary-400` al 18 % | Columna del día de hoy en la Pizarra |
| `overdue` · compartido | `#c4554d` | `#e06a62` | Proyecto, nota o tarea vencidos |
| `--wb-scrollbar-thumb` / `-hover` · derivado | `ink` al 18 % / 32 % | `ink` al 15 % / 28 % | Barra de scroll sutil (utilidad `scrollbar-subtle`, sin clase de Tailwind propia) |

Además: `--wb-brand-*` (colores del isotipo; compartidos, el logo es roble en todas las paletas), `--wb-glow-*` (paleta) y `--wb-grid-dot` (derivado) para el fondo de la landing, y `--wb-chart-1` … `--wb-chart-6` (series de gráficos: la 1 es el acento de la paleta; 2 a 6 compartidas: azul, verde, naranja, morado, rosa. Océano cambia la 2 y Bosque la 3 por roble para no repetir su acento). Los lee `resources/js/modules/chart.js`, no hay utilidad de Tailwind para ellos.

**Agregar un color**: primero preguntarse si es un rol nuevo (token) o un tono nuevo (escala). Un rol nuevo se agrega en `tokens.css` (compartido o derivado) o en el contrato de paletas (si cambia por paleta: entonces en **todas** las paletas), con ambos valores y su línea en `@theme inline`; luego se documenta en esta tabla.

### 3. Paletas (`resources/css/theme/palettes/`)

El usuario elige la paleta en **Ajustes → Apariencia**; se guarda en su cuenta (`users.palette`, enum `App\Enums\ColorPalette`). Hay dos ejes independientes sobre `<html>`:

- **Modo**: clase `dark` (por navegador, ver «Modo claro / oscuro»).
- **Paleta**: atributo `data-palette` (por usuario). Lo pinta el servidor en `layouts::base` (invitados y landing: la paleta por defecto), así que no hay destello y sobrevive a `wire:navigate`. Al elegir otra en Ajustes, el componente despacha `palette-changed` y `resources/js/modules/palette.js` cambia el atributo sin recargar; los gráficos se re-tematizan solos.

| Paleta | Acento (`primary-500`) | Neutros |
| --- | --- | --- |
| `roble` (por defecto) | `#8f6b4a` nogal | Cálidos tipo Notion |
| `oceano` | `#3a6b8c` azul pizarra | Fríos |
| `bosque` | `#4f7a5a` verde salvia | Verdosos suaves |

**Contrato**: cada archivo define, en `[data-palette='x']`, las cuatro escalas `--wb-{primary,secondary,gray,dark}-{50…950}` y las superficies claras (`canvas`, `sidebar`, `surface`, `surface-muted`, `elevated`, `edge`, `ink`, `ink-muted`, `ink-subtle`, `line`, `line-strong`, `chart-1`, `glow-a`, `glow-b`); y en `:is(.dark[data-palette='x'], .dark [data-palette='x'])` esas mismas superficies en oscuro. Lo derivado no se repite. La forma `:is(...)` también aplica a un elemento anidado, por eso las tarjetas del selector (`<x-theme.palette-option>`) se pintan con la paleta que muestran.

**Agregar una paleta**:

1. Copiar `palettes/roble.css` como `palettes/<nombre>.css` y cambiar los valores (contraste AA para texto blanco sobre `primary-500` y `primary-600`).
2. Importarla en `palettes/index.css`.
3. Agregar el case en `App\Enums\ColorPalette` con `label()` y `description()`.
4. `npm run build`. Documentarla en la tabla de arriba.

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

- Colores: por las escalas de `scales.css`, que siguen a la paleta activa (no hace falta tocar componentes). Colores por componente publicados en `app/View/Components/TallStackUi/Colors` (hoy: el botón «Cancelar» de los diálogos es neutro, `secondary`, para no confundirse con la acción destructiva).
- Íconos: Heroicons en estilo `outline` (`config/tallstackui.php`).
- Ajustes de clases que los tokens no alcanzan: `App\Providers\TallStackUiServiceProvider` (soft customization). Hoy: tarjetas planas (`x-card` con borde fino y sin sombra), paneles superpuestos (`x-slide`, `x-dialog` y `x-modal`: velo `overlay` con leve desenfoque, panel `elevated` con borde `edge` y sombra marcada) y colores neutros del theme switch. Toda personalización nueva del kit va en ese provider, con un método por componente.

## Movimiento

- Animaciones solo con `transform` y `opacity`, lentas y discretas.
- Siempre respetar `prefers-reduced-motion` (ver `resources/css/components/landing.css`).
