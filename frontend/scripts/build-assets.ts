import { spawn } from 'node:child_process';
import { cp, mkdir, rm } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { readFile, writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { stripCssBanner } from './strip-css-banner.ts';

type Manifest = Record<string, unknown>;

const viteBin = fileURLToPath(new URL('../node_modules/vite/bin/vite.js', import.meta.url));
const manifestPath = fileURLToPath(new URL('../dist/manifest.json', import.meta.url));
const distDir = fileURLToPath(new URL('../dist', import.meta.url));
const publicAssetsDir = fileURLToPath(new URL('../../public/assets', import.meta.url));

function runViteBuild(target: 'app' | 'login', emptyOutDir: boolean): Promise<void> {
    return new Promise((resolve, reject) => {
        const child = spawn(process.execPath, [viteBin, 'build'], {
            stdio: 'inherit',
            env: {
                ...process.env,
                EXECUTO_BUILD_TARGET: target,
                EXECUTO_BUILD_EMPTY: emptyOutDir ? 'true' : 'false',
            },
        });

        child.on('error', reject);
        child.on('exit', (code) => {
            if (code === 0) {
                resolve();

                return;
            }

            reject(new Error(`Vite ${target} build failed with exit code ${code ?? 'unknown'}.`));
        });
    });
}

async function readManifest(): Promise<Manifest> {
    return JSON.parse(await readFile(manifestPath, 'utf8')) as Manifest;
}

async function copyAssetsToPublic(): Promise<void> {
    const subdirs = ['js', 'css', 'fonts', 'icons'];

    for (const dir of subdirs) {
        const src = `${distDir}/${dir}`;
        const dest = `${publicAssetsDir}/${dir}`;

        await rm(dest, { recursive: true, force: true });

        if (existsSync(src)) {
            await mkdir(dest, { recursive: true });
            await cp(src, dest, { recursive: true });
        }
    }

    // Remove stale manifest from public/ if it was left by a previous build setup
    await rm(`${publicAssetsDir}/manifest.json`, { force: true });
}

await runViteBuild('app', true);
const appManifest = await readManifest();

await runViteBuild('login', false);
const loginManifest = await readManifest();

await writeFile(manifestPath, `${JSON.stringify({ ...appManifest, ...loginManifest }, null, 2)}\n`, 'utf8');
await copyAssetsToPublic();
await stripCssBanner();
