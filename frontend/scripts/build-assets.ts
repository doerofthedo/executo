import { spawn } from 'node:child_process';
import { readFile, writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { stripCssBanner } from './strip-css-banner.ts';

type Manifest = Record<string, unknown>;

const viteBin = fileURLToPath(new URL('../node_modules/vite/bin/vite.js', import.meta.url));
const manifestPath = fileURLToPath(new URL('../../public/assets/manifest.json', import.meta.url));

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

await runViteBuild('app', true);
const appManifest = await readManifest();

await runViteBuild('login', false);
const loginManifest = await readManifest();

await writeFile(manifestPath, `${JSON.stringify({ ...appManifest, ...loginManifest }, null, 2)}\n`, 'utf8');
await stripCssBanner();
