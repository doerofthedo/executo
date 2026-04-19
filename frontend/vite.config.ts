import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';
import { resolve } from 'node:path';

export default defineConfig({
    plugins: [vue(), tailwindcss()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url)),
        },
    },
    server: {
        host: '0.0.0.0',
        port: 80,
        allowedHosts: ['executo.local'],
        hmr: {
            path: '/vite-hmr',
        },
    },
    build: {
        outDir: '../public/assets',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                app: resolve(__dirname, 'src/entries/app.ts'),
                auth: resolve(__dirname, 'src/entries/auth.ts'),
            },
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name?.endsWith('.css')) {
                        return 'css/[name][extname]';
                    }

                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
});
