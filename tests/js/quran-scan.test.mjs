import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

globalThis.window = { location: { origin: 'https://pkgenerus.test' } };

async function scannerModule() {
    let source = await readFile(new URL('../../resources/js/quran-scan.js', import.meta.url), 'utf8');
    source = source.replace("import { fetchWithFreshCsrf, refreshCsrfToken } from './csrf-session';", '');
    source = source.slice(0, source.indexOf('\nshowChromeNotices();'));
    return import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
}

const scanner = await scannerModule();

test('quick file scan keeps the direct decoder fast path', async () => {
    let fallbackUsed = false;
    const result = await scanner.scanQuickFile({
        scanFile: async () => 'PKGQ:0123456789ABCDEF0123456789ABCDEF:ABCDEF0123456789ABCDEF0123456789',
    }, { name: 'close-up.png' }, {
        loadImage: async () => { fallbackUsed = true; },
    });

    assert.equal(result, 'PKGQ:0123456789ABCDEF0123456789ABCDEF:ABCDEF0123456789ABCDEF0123456789');
    assert.equal(fallbackUsed, false);
});

test('quick file scan falls back to the crop pipeline after a full-image failure', async () => {
    const image = { naturalWidth: 1654, naturalHeight: 2339 };
    const canvas = { width: 1654, height: 2339 };
    let readArguments;
    const result = await scanner.scanQuickFile({
        scanFile: async () => { throw new Error('QR too small'); },
    }, { name: 'full-page.png' }, {
        loadImage: async () => image,
        makeCanvas: (loaded) => {
            assert.equal(loaded, image);
            return canvas;
        },
        read: async (...arguments_) => {
            readArguments = arguments_;
            return { payload: 'PKGQMB:0123456789ABCDEF0123456789ABCDEF:ABCDEF0123456789ABCDEF0123456789' };
        },
    });

    assert.equal(readArguments[1], canvas);
    assert.equal(result, 'PKGQMB:0123456789ABCDEF0123456789ABCDEF:ABCDEF0123456789ABCDEF0123456789');
});

test('QR payload normalization accepts only supported PKG payloads and public scan URLs', () => {
    const uuid = '00112233445566778899aabbccddeeff';
    const token = 'ffeeddccbbaa99887766554433221100';
    const publicCode = Buffer.from([2, ...Buffer.from(uuid + token, 'hex')]).toString('base64url');

    assert.equal(scanner.normalizeQrPayload(`https://pkgenerus.test/sq/${publicCode}`), `PKGQ:${uuid.toUpperCase()}:${token.toUpperCase()}`);
    assert.equal(scanner.normalizeQrPayload('https://pkgenerus.test/sq/not-a-pkg-code'), null);
    assert.equal(scanner.normalizeQrPayload('untrusted-payload'), null);
});
