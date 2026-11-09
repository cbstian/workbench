# Marca — Workbench

Assets de identidad visual: qué hay, para qué sirve cada uno y cómo regenerarlos.

## Diagnóstico de los originales

Los cuatro archivos entregados (`resources/images/brand/originales/*.jpg`) son renders fotográficos (bloque de madera con letras metálicas). Como logo para una interfaz web tenían estos problemas:

| Problema | Efecto |
| --- | --- |
| JPG sin transparencia, con fondo gris/crema y sombra proyectada | No se pueden poner sobre otro color sin que se vea el "cuadro" del fondo |
| Mucho espacio vacío (el bloque ocupa ~45 % de la imagen) y lienzo vertical 784×1168 en los íconos | No se pueden usar como ícono cuadrado; se ven chicos |
| Textura fotográfica (veta de madera, brillo metálico) | A 16–32 px (favicon, sidebar) se convierte en una mancha ilegible |
| 220–280 KB cada uno | Demasiado pesados para un logo |
| No vectorizables fielmente | Un trazado automático (potrace) de una foto pierde la textura y deja bordes sucios |

## Qué se hizo

1. **Originales intactos** en `resources/images/brand/originales/` (referencia, no se usan en la interfaz).
2. **Versiones fotográficas recortadas** (`icono-*.webp`, `logo-*.webp`): recorte al bloque, esquinas redondeadas con fondo transparente, sin sombra, ícono cuadrado 512×512 y logo de 960 px de ancho. WebP de 60–80 KB. Para usos grandes (presentaciones, portada, correo de marca).
3. **Versiones vectoriales planas** (`isotipo-*.svg`, `logotipo-*.svg`): rediseño plano que conserva la idea (bloque de madera + letra metálica) con los colores medidos en las fotos y la letra en **Space Grotesk** convertida a trazos (no depende de la fuente instalada). Son las que usa la interfaz y escalan a cualquier tamaño.
4. **Set de favicons** en `public/`: `favicon.svg` (cambia solo entre claro/oscuro según el sistema), `favicon.ico` (16/32/48), `apple-touch-icon.png` (180, sin transparencia como pide iOS), `icon-192.png`, `icon-512.png`, `icon-maskable-512.png` y `site.webmanifest`.

## Paleta de marca

| Variante | Bloque | Canto | Letra | Texto del logotipo |
| --- | --- | --- | --- | --- |
| Claro | `#D8B48E` (roble) | `#B98F66` | `#FBFAF8` (metal claro) | `#2F2D2A` |
| Oscuro | `#4B3429` (nogal) | `#2E1F18` | `#DCB67C` (latón) | `#EDEBE7` |

## Cuándo usar cada archivo

| Archivo | Uso |
| --- | --- |
| `isotipo-claro.svg` / `isotipo-oscuro.svg` | Ícono solo (sidebar colapsado, avatar de la app). Claro sobre fondos claros, oscuro sobre fondos oscuros |
| `logotipo-claro.svg` / `logotipo-oscuro.svg` | Isotipo + palabra "workbench" para documentos o correos |
| `icono-*.webp`, `logo-*.webp` | Piezas grandes donde la textura de madera aporta |
| Componente `<x-brand.logo />` | **En la interfaz usar siempre este componente**: dibuja el isotipo según el tema activo y la palabra en Space Grotesk como texto real |

## Regenerar

El script `docs/marca/generar-assets.py` reproduce todo desde los originales (instrucciones en su cabecera). Si cambian los originales, ajustar las coordenadas de recorte en `BOXES`.
