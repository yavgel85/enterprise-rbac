import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Inside the Docker `vite` service the dev server must bind to all interfaces
// and use file-polling (bind-mount fs events are unreliable), while the HMR
// websocket the *browser* connects to still points at localhost.
const inDocker = process.env.VITE_DOCKER === 'true';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        host: inDocker ? '0.0.0.0' : 'localhost',
        port: 5173,
        ...(inDocker
            ? {
                  strictPort: true,
                  hmr: { host: 'localhost' },
              }
            : {}),
        watch: {
            ignored: ['**/storage/framework/views/**'],
            ...(inDocker ? { usePolling: true } : {}),
        },
    },
});
