#!/usr/bin/env node
// Fail a release if Laravel's Vite manifest points at an absent build artifact.
import { readFile, stat } from 'node:fs/promises';
import path from 'node:path';

const buildDirectory = path.resolve(process.argv[2] ?? 'public/build');
const manifestPath = path.join(buildDirectory, 'manifest.json');
const manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
const referencedFiles = new Set();

for (const entry of Object.values(manifest)) {
    if (!entry || typeof entry !== 'object') {
        continue;
    }

    for (const field of ['file', 'css', 'assets']) {
        const values = Array.isArray(entry[field]) ? entry[field] : [entry[field]];
        for (const value of values) {
            if (typeof value === 'string' && value.length > 0) {
                referencedFiles.add(value);
            }
        }
    }
}

const missingFiles = [];
for (const file of referencedFiles) {
    const resolvedPath = path.resolve(buildDirectory, file);
    if (!resolvedPath.startsWith(`${buildDirectory}${path.sep}`)) {
        missingFiles.push(`${file} (path escapes build directory)`);
        continue;
    }

    try {
        if (!(await stat(resolvedPath)).isFile()) {
            missingFiles.push(file);
        }
    } catch {
        missingFiles.push(file);
    }
}

if (missingFiles.length > 0) {
    console.error(`Vite manifest references missing assets:\n${missingFiles.sort().join('\n')}`);
    process.exit(1);
}

console.log(`Verified ${referencedFiles.size} Vite manifest assets in ${buildDirectory}.`);
