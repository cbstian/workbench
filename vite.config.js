import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            /*
             * Fonts are downloaded from Bunny Fonts at build time and served
             * from public/build, so no request leaves the app at runtime.
             * They are exposed as CSS variables (--font-inter, --font-space-grotesk)
             * through the @fonts Blade directive. See docs/arquitectura/diseno.md.
             */
            fonts: [
                bunny('Inter', {
                    weights: [400, 500, 600, 700],
                    subsets: ['latin', 'latin-ext'],
                    preload: [{ weight: 400 }, { weight: 500 }],
                }),
                bunny('Space Grotesk', {
                    weights: [500, 600, 700],
                    subsets: ['latin', 'latin-ext'],
                    preload: [{ weight: 600 }],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
