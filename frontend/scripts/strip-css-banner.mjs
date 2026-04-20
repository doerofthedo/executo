import { readdir, readFile, writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const cssDir = fileURLToPath(new URL('../../public/assets/css', import.meta.url));
const bannerPattern = /^\/\*! tailwindcss v[\d.]+ \| MIT License \| https:\/\/tailwindcss\.com \*\/\s*/u;

async function main() {
    let entries = [];

    try {
        entries = await readdir(cssDir, { withFileTypes: true });
    } catch {
        return;
    }

    await Promise.all(
        entries
            .filter((entry) => entry.isFile() && entry.name.endsWith('.css'))
            .map(async (entry) => {
                const filePath = path.join(cssDir, entry.name);
                const source = await readFile(filePath, 'utf8');
                const updated = source.replace(bannerPattern, '');

                if (updated !== source) {
                    await writeFile(filePath, updated, 'utf8');
                }
            })
    );
}

await main();
