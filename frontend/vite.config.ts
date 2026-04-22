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
        manifest: 'manifest.json',
        rollupOptions: {
            input: {
                shared: resolve(__dirname, 'src/entries/shared.ts'),
                app: resolve(__dirname, 'src/entries/app.ts'),
                login: resolve(__dirname, 'src/entries/login.ts'),
            },
            output: {
                entryFileNames: 'js/[name]-[hash].js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name?.endsWith('.css')) {
                        return 'css/[name]-[hash][extname]';
                    }

                    if (assetInfo.name !== undefined && /\.(woff2?|ttf|otf|eot)$/i.test(assetInfo.name)) {
                        return 'fonts/[name]-[hash][extname]';
                    }

                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
});
