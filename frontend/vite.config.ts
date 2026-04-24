import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';
import { resolve } from 'node:path';
import { splitLoginCss } from './scripts/vite-plugin-split-css';

const buildTarget = process.env.EXECUTO_BUILD_TARGET;
const isSingleEntryBuild = buildTarget === 'app' || buildTarget === 'login';
const appInput = resolve(__dirname, 'src/entries/app.ts');
const loginInput = resolve(__dirname, 'src/entries/login.ts');

function buildInput(): Record<string, string> {
    if (buildTarget === 'app') {
        return { app: appInput };
    }

    if (buildTarget === 'login') {
        return { login: loginInput };
    }

    return {
        app: appInput,
        login: loginInput,
    };
}

export default defineConfig(({ command }) => ({
    base: command === 'build' ? '/assets/' : '/',
    plugins: [vue(), tailwindcss(), splitLoginCss()],
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
        emptyOutDir: process.env.EXECUTO_BUILD_EMPTY !== 'false',
        manifest: 'manifest.json',
        rollupOptions: {
            input: buildInput(),
            output: {
                ...(isSingleEntryBuild ? { codeSplitting: false } : {}),
                entryFileNames: 'js/[name]-[hash].js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name?.endsWith('.css')) {
                        return 'css/[name]-[hash][extname]';
                    }

                    if (assetInfo.name !== undefined && /\.(woff2?|ttf|otf|eot)$/i.test(assetInfo.name)) {
                        return 'fonts/[name]-[hash][extname]';
                    }

                    if (assetInfo.name !== undefined && /\.svg$/i.test(assetInfo.name)) {
                        return 'icons/[name]-[hash][extname]';
                    }

                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
}));
