import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/filament-osm.js'
            ],
            refresh: [
                ...refreshPaths,
                'app/Filament/**',
                'app/Livewire/**',
                'app/Forms/Components/**',
            ],
        }),
    ],
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
    },
});

