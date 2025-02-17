import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    server: {
        hmr: {
            host: 'localhost'
        }
    },
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
            refresh: true,
        }),
        vue({
            template: {
                compilerOptions: {
                    isCustomElement: (tag) => {
                        return tag.startsWith('vc-'); // Pour les composants v-calendar
                    }
                }
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
});
