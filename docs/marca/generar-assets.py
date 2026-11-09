"""Genera los assets de marca de Workbench a partir de los JPG originales y de Space Grotesk.

Uso (desde la raíz del proyecto):

    pip install pillow numpy fonttools cairosvg
    curl -L -o /tmp/SpaceGrotesk.ttf 'https://github.com/google/fonts/raw/main/ofl/spacegrotesk/SpaceGrotesk%5Bwght%5D.ttf'
    python3 docs/marca/generar-assets.py

Escribe en resources/images/brand/ y en public/. Ver docs/marca/README.md.
"""
from pathlib import Path
import io
import numpy as np
from PIL import Image, ImageDraw, ImageFilter
from fontTools.ttLib import TTFont
from fontTools.varLib import instancer
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.pens.boundsPen import BoundsPen
import cairosvg

ROOT = Path(__file__).resolve().parents[2]
ORIGINALS = ROOT / 'resources/images/brand/originales'
BRAND = ROOT / 'resources/images/brand'
PUBLIC = ROOT / 'public'
FONT = Path('/tmp/SpaceGrotesk.ttf')

# ---------- 1) Recorte de las fotos originales ----------
BOXES = {
    'icono-claro': ((74, 244, 708, 895), 0.085),
    'icono-oscuro': ((73, 222, 703, 894), 0.085),
    'logo-claro': ((146, 216, 1025, 552), 0.05),
    'logo-oscuro': ((180, 200, 985, 556), 0.05),
}

def rounded_mask(size, radius_ratio, scale=4):
    w, h = size
    big = Image.new('L', (w * scale, h * scale), 0)
    r = int(min(w, h) * radius_ratio * scale)
    ImageDraw.Draw(big).rounded_rectangle((0, 0, w * scale - 1, h * scale - 1), radius=r, fill=255)
    return big.resize((w, h), Image.LANCZOS)

for name, (box, rr) in BOXES.items():
    im = Image.open(ORIGINALS / f'{name}.jpg').convert('RGB')
    l, t, r, b = box
    inset = 2
    crop = im.crop((l + inset, t + inset, r - inset, b - inset)).convert('RGBA')
    crop.putalpha(rounded_mask(crop.size, rr))
    if name.startswith('icono'):
        side = max(crop.size)
        canvas = Image.new('RGBA', (side, side), (0, 0, 0, 0))
        canvas.paste(crop, ((side - crop.width) // 2, (side - crop.height) // 2), crop)
        canvas = canvas.resize((512, 512), Image.LANCZOS)
    else:
        target_w = 960
        canvas = crop.resize((target_w, round(crop.height * target_w / crop.width)), Image.LANCZOS)
    canvas.save(BRAND / f'{name}.webp', quality=88, method=6)

# ---------- 2) Versiones vectoriales planas con Space Grotesk ----------
def font_at(weight):
    return instancer.instantiateVariableFont(TTFont(FONT), {'wght': weight})

def text_path(font, text, size):
    """Devuelve (d, ancho, ymin, ymax) del texto convertido a trazos, con origen en la línea base."""
    gs = font.getGlyphSet()
    cmap = font.getBestCmap()
    upm = font['head'].unitsPerEm
    s = size / upm
    x = 0
    parts = []
    ymin, ymax = 1e9, -1e9
    for ch in text:
        gname = cmap[ord(ch)]
        pen = SVGPathPen(gs, ntos=lambda v: f'{v:.2f}'.rstrip('0').rstrip('.'))
        tpen = TransformPen(pen, (s, 0, 0, -s, x, 0))
        gs[gname].draw(tpen)
        bp = BoundsPen(gs)
        gs[gname].draw(bp)
        if bp.bounds:
            ymin = min(ymin, -bp.bounds[3] * s)
            ymax = max(ymax, -bp.bounds[1] * s)
        parts.append(pen.getCommands())
        x += gs[gname].width * s
    return ' '.join(parts), x, ymin, ymax

bold = font_at(700)
semi = font_at(600)

PALETTE = {
    'claro': {'tile': '#D8B48E', 'edge': '#B98F66', 'glyph': '#FBFAF8', 'glyph_shadow': '#8A6847', 'ink': '#2F2D2A'},
    'oscuro': {'tile': '#4B3429', 'edge': '#2E1F18', 'glyph': '#DCB67C', 'glyph_shadow': '#1C130E', 'ink': '#EDEBE7'},
}

w_d, w_w, w_ymin, w_ymax = text_path(bold, 'W', 42)
w_h = w_ymax - w_ymin

def tile_group(p, size=64, radius=14, full_bleed=False):
    gx = (size - w_w) / 2
    gy = (size - w_h) / 2 - w_ymin - 1.5
    r = 0 if full_bleed else radius
    edge = f'<rect x="0" y="0" width="{size}" height="{size}" rx="{r}" fill="{p["edge"]}"/>'
    top = f'<rect x="0" y="0" width="{size}" height="{size - 3}" rx="{r}" fill="{p["tile"]}"/>'
    shadow = f'<path transform="translate({gx:.2f} {gy + 1.2:.2f})" fill="{p["glyph_shadow"]}" fill-opacity=".35" d="{w_d}"/>'
    glyph = f'<path transform="translate({gx:.2f} {gy:.2f})" fill="{p["glyph"]}" d="{w_d}"/>'
    return edge + top + shadow + glyph

def svg(content, w, h, title):
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w:.0f} {h:.0f}" width="{w:.0f}" height="{h:.0f}" role="img" aria-label="{title}">'
            f'<title>{title}</title>{content}</svg>\n')

for variant, p in PALETTE.items():
    (BRAND / f'isotipo-{variant}.svg').write_text(svg(tile_group(p), 64, 64, 'Workbench'))

    # Logotipo: isotipo + palabra "workbench" en Space Grotesk 600.
    t_d, t_w, t_ymin, t_ymax = text_path(semi, 'workbench', 40)
    gap = 14
    height = 64
    baseline = (height - (t_ymax - t_ymin)) / 2 - t_ymin
    width = 64 + gap + t_w
    content = tile_group(p) + f'<path transform="translate({64 + gap} {baseline:.2f})" fill="{p["ink"]}" d="{t_d}"/>'
    (BRAND / f'logotipo-{variant}.svg').write_text(svg(content, width, height, 'Workbench'))

# Favicon SVG adaptable al tema del sistema.
c, d = PALETTE['claro'], PALETTE['oscuro']
fav = (
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
    f'<style>.e{{fill:{c["edge"]}}}.t{{fill:{c["tile"]}}}.s{{fill:{c["glyph_shadow"]};fill-opacity:.35}}.g{{fill:{c["glyph"]}}}'
    f'@media (prefers-color-scheme:dark){{.e{{fill:{d["edge"]}}}.t{{fill:{d["tile"]}}}.s{{fill:{d["glyph_shadow"]}}}.g{{fill:{d["glyph"]}}}}}</style>'
)
gx = (64 - w_w) / 2
gy = (64 - w_h) / 2 - w_ymin - 1.5
fav += (f'<rect class="e" width="64" height="64" rx="14"/><rect class="t" width="64" height="61" rx="14"/>'
        f'<path class="s" transform="translate({gx:.2f} {gy + 1.2:.2f})" d="{w_d}"/>'
        f'<path class="g" transform="translate({gx:.2f} {gy:.2f})" d="{w_d}"/></svg>\n')
(PUBLIC / 'favicon.svg').write_text(fav)

def render(svg_text, px):
    return Image.open(io.BytesIO(cairosvg.svg2png(bytestring=svg_text.encode(), output_width=px, output_height=px))).convert('RGBA')

light_tile = svg(tile_group(PALETTE['claro']), 64, 64, 'Workbench')
dark_tile = svg(tile_group(PALETTE['oscuro']), 64, 64, 'Workbench')
bleed = svg(tile_group(PALETTE['oscuro'], full_bleed=True), 64, 64, 'Workbench')

ico_frames = [render(dark_tile, s) for s in (16, 32, 48)]
ico_frames[-1].save(PUBLIC / 'favicon.ico', sizes=[(16, 16), (32, 32), (48, 48)], append_images=ico_frames[:-1])
render(bleed, 180).convert('RGB').save(PUBLIC / 'apple-touch-icon.png', optimize=True)
render(dark_tile, 192).save(PUBLIC / 'icon-192.png', optimize=True)
render(dark_tile, 512).save(PUBLIC / 'icon-512.png', optimize=True)

# Maskable: el isotipo ocupa el 80 % central (zona segura).
mask_svg = svg(f'<rect width="80" height="80" fill="{PALETTE["oscuro"]["tile"]}"/><g transform="translate(8 8)">{tile_group(PALETTE["oscuro"])}</g>', 80, 80, 'Workbench')
render(mask_svg, 512).convert('RGB').save(PUBLIC / 'icon-maskable-512.png', optimize=True)

print('Assets de marca generados.')
