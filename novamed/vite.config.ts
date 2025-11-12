import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import tailwindcss from "@tailwindcss/vite";
import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import Components from 'unplugin-vue-components/vite';
import { PrimeVueResolver } from 'unplugin-vue-components/resolvers';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts', 'resources/css/app.css'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
            compilerOptions: {
                isCustomElement: (tag) => tag.startsWith('p-')
            }
        }),
        Components({
            resolvers: [
                PrimeVueResolver() // To jest "magia", która wszystko naprawi
            ]
        })
    ],

    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
    server: {
        fs: {
            strict: true
        }
    },
    build: {
        manifest: true,
        outDir: 'public/build',
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['vue', 'vue-router', '@vueuse/core'],
                    'ui-lib': ['radix-vue', 'reka-ui'],
                    'primevue': ['primevue/usetoast'],
                    'charts': ['chart.js'],
                    'utils': ['axios', 'vuedraggable', 'lucide-vue-next'],
                }
            }
        }
    }

});
