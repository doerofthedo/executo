import { readdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import type { Dirent } from 'node:fs';

const cssDir = fileURLToPath(new URL('../../public/assets/css', import.meta.url));
const bannerPattern = /^\/\*! tailwindcss v[\d.]+ \| MIT License \| https:\/\/tailwindcss\.com \*\/\s*/u;

export async function stripCssBanner(): Promise<void> {
    let entries: Dirent[] = [];

    try {
        entries = await readdir(cssDir, { withFileTypes: true });
    } catch {
        return;
    }

    await Promise.all(
        entries
            .filter((entry) => entry.isFile() && entry.name.endsWith('.css'))
            .map(async (entry) => {
                const filePath = join(cssDir, entry.name);
                const source = await readFile(filePath, 'utf8');
                const updated = source.replace(bannerPattern, '');

                if (updated !== source) {
                    await writeFile(filePath, updated, 'utf8');
                }
            })
    );
}

if (process.argv[1] !== undefined && import.meta.url === pathToFileURL(process.argv[1]).href) {
    await stripCssBanner();
}
