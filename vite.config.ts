import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, type PluginOption } from 'vite';

// Vite-only container has no PHP; routes are generated in the app container first.
const skipWayfinderPlugin = process.env.WAYFINDER_SKIP_PLUGIN === '1';

const plugins: PluginOption[] = [
    laravel({
        input: ['resources/css/app.css', 'resources/js/app.tsx'],
        refresh: true,
        fonts: [
            bunny('Instrument Sans', {
                weights: [400, 500, 600],
            }),
        ],
    }),
    inertia(),
    react({
        babel: {
            plugins: ['babel-plugin-react-compiler'],
        },
    }),
    tailwindcss(),
];

if (!skipWayfinderPlugin) {
    plugins.push(
        wayfinder({
            formVariants: true,
        }),
    );
}

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
    },
    plugins,
});
