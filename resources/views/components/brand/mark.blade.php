{{--
    Workbench isotype (wooden block with a "W" in Space Grotesk).

    Inline SVG whose colors come from the --wb-brand-* tokens, so it follows
    the active theme automatically. Size it with classes: <x-brand.mark class="size-6" />
--}}
<svg {{ $attributes->class('shrink-0') }} viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <rect width="64" height="64" rx="14" fill="var(--wb-brand-edge)" />
    <rect width="64" height="61" rx="14" fill="var(--wb-brand-tile)" />
    <path transform="translate(13.14 46.4)" fill="var(--wb-brand-shadow)" d="M5.12 0 1.26 -29.4H6.76L9.62 -3.86H10.37L14.07 -29.4H23.65L27.34 -3.86H28.1L30.95 -29.4H36.46L32.59 0H23.02L19.24 -26.12H18.48L14.7 0Z" />
    <path transform="translate(13.14 45.2)" fill="var(--wb-brand-glyph)" d="M5.12 0 1.26 -29.4H6.76L9.62 -3.86H10.37L14.07 -29.4H23.65L27.34 -3.86H28.1L30.95 -29.4H36.46L32.59 0H23.02L19.24 -26.12H18.48L14.7 0Z" />
</svg>
