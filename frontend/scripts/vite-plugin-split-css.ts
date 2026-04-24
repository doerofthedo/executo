import type { Plugin } from 'vite';
import { createHash } from 'node:crypto';
import { readFile, unlink, writeFile } from 'node:fs/promises';
import { basename, join } from 'node:path';

type ManifestEntry = {
    css?: string[];
    [key: string]: unknown;
};

type Manifest = Record<string, ManifestEntry>;
type CssOutputAsset = {
    type: 'asset';
    fileName: string;
    source: string;
};

const loginBlockPattern = /\/\*! @login start \*\/([\s\S]*?)\/\*! @login end \*\//gu;
const loginMarkerPattern = /\/\*! @login (?:start|end) \*\/\s*/gu;
const loginBoundarySelector = '.__executo-login-css-end';
const loginBoundaryPattern = /\.__executo-login-css-end\{display:contents\}/u;

function assetHash(source: string): string {
    return createHash('sha256')
        .update(source)
        .digest('base64url')
        .slice(0, 8);
}

function extractLoginCss(source: string): string {
    const blocks = [...source.matchAll(loginBlockPattern)]
        .map((match) => match[1]?.trim())
        .filter((block): block is string => block !== undefined && block.length > 0);

    if (blocks.length > 0) {
        return `${blocks.join('\n\n')}\n`.replace(loginMarkerPattern, '');
    }

    const boundary = source.search(loginBoundaryPattern);

    if (boundary === -1) {
        throw new Error('Could not find the login CSS boundary in the generated app stylesheet.');
    }

    return `${source.slice(0, boundary).replace(loginMarkerPattern, '').trim()}\n`;
}

function updateManifest(
    source: string,
    generatedCssFileName: string,
    appCssFileName: string,
    loginCssFileName: string
): string {
    const manifest = JSON.parse(source) as Manifest;
    const appEntry = manifest['src/entries/app.ts'];
    const loginEntry = manifest['src/entries/login.ts'];

    if (appEntry === undefined && loginEntry === undefined) {
        throw new Error('Could not find a known Executo entry in the Vite manifest.');
    }

    if (appEntry !== undefined) {
        appEntry.css = [appCssFileName];
    }

    if (loginEntry !== undefined) {
        loginEntry.css = [loginCssFileName];
    }

    for (const [key, entry] of Object.entries(manifest)) {
        if (entry.file === generatedCssFileName) {
            delete manifest[key];
            continue;
        }

        if (key === 'src/entries/app.ts' || key === 'src/entries/login.ts') {
            continue;
        }

        if (entry.css === undefined) {
            continue;
        }

        entry.css = entry.css.filter(
            (cssFileName) =>
                cssFileName !== generatedCssFileName &&
                cssFileName !== appCssFileName &&
                cssFileName !== loginCssFileName &&
                basename(cssFileName) !== basename(appCssFileName) &&
                basename(cssFileName) !== basename(loginCssFileName)
        );

        if (entry.css.length === 0) {
            delete entry.css;
        }
    }

    return `${JSON.stringify(manifest, null, 2)}\n`;
}

function findAppCssAsset(bundle: Record<string, unknown>): CssOutputAsset {
    const cssAssets = Object.values(bundle).filter(
        (asset): asset is CssOutputAsset =>
            typeof asset === 'object' &&
            asset !== null &&
            'type' in asset &&
            asset.type === 'asset' &&
            'fileName' in asset &&
            typeof asset.fileName === 'string' &&
            asset.fileName.endsWith('.css') &&
            'source' in asset &&
            typeof asset.source === 'string'
    );

    const markedAsset = cssAssets.find(
        (asset) => asset.source.includes('/*! @login start */') || asset.source.includes(loginBoundarySelector)
    );

    if (markedAsset !== undefined) {
        return markedAsset;
    }

    const appAsset = cssAssets.find((asset) => /^css\/app-[\w-]+\.css$/u.test(asset.fileName));

    if (appAsset !== undefined) {
        return appAsset;
    }

    throw new Error('Could not find the generated app CSS asset.');
}

export function splitLoginCss(): Plugin {
    return {
        name: 'executo-split-login-css',
        apply: 'build',
        enforce: 'post',
        async writeBundle(options, bundle) {
            const outDir = options.dir;

            if (outDir === undefined) {
                throw new Error('Vite output directory is required to split login CSS.');
            }

            const appCssAsset = findAppCssAsset(bundle);

            const appCssFileName = appCssAsset.fileName;
            const appCssPath = join(outDir, appCssFileName);
            const appCss = await readFile(appCssPath, 'utf8');
            const appCssOutput = appCss.replace(loginBoundaryPattern, '').replace(loginMarkerPattern, '');
            const renamedAppCssFileName = `css/app-${assetHash(appCssOutput)}.css`;
            const loginCss = extractLoginCss(appCss);
            const loginCssFileName = `css/login-${assetHash(loginCss)}.css`;
            const manifestPath = join(outDir, 'manifest.json');
            const manifest = await readFile(manifestPath, 'utf8');
            const parsedManifest = JSON.parse(manifest) as Manifest;
            const hasAppEntry = parsedManifest['src/entries/app.ts'] !== undefined;
            const hasLoginEntry = parsedManifest['src/entries/login.ts'] !== undefined;

            if (hasAppEntry) {
                await writeFile(join(outDir, renamedAppCssFileName), appCssOutput, 'utf8');
            }

            if (hasLoginEntry) {
                await writeFile(join(outDir, loginCssFileName), loginCss, 'utf8');
            }

            await writeFile(
                manifestPath,
                updateManifest(manifest, appCssFileName, renamedAppCssFileName, loginCssFileName),
                'utf8'
            );

            if (appCssFileName !== renamedAppCssFileName) {
                await unlink(appCssPath);
            }
        },
    };
}
