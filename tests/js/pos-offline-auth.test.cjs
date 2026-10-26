const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { runInNewContext } = require('node:vm');
const { test } = require('node:test');
const { webcrypto } = require('node:crypto');

const browserScript = readFileSync(
    new URL('../../public/js/pos-offline-auth.js', `file://${__dirname}/`),
    'utf8'
);
const browserModule = { exports: {} };
runInNewContext(browserScript, {
    module: browserModule,
    TextEncoder,
    Uint8Array,
    Array,
    Error,
    RegExp,
    parseInt,
    Math,
    String,
    JSON,
});
const { deriveVerifier, normalizeLoginIdentifier, verifierMatches } = browserModule.exports;

test('offline password verifier is salted and distinguishes passwords', async () => {
    const salt = '00112233445566778899aabbccddeeff';
    const verifier = await deriveVerifier('correct horse battery staple', salt, 1000, webcrypto);
    const sameVerifier = await deriveVerifier('correct horse battery staple', salt, 1000, webcrypto);
    const otherPassword = await deriveVerifier('different password', salt, 1000, webcrypto);
    const otherSalt = await deriveVerifier('correct horse battery staple', 'ffeeddccbbaa99887766554433221100', 1000, webcrypto);

    assert.match(verifier, /^[0-9a-f]{64}$/);
    assert.equal(verifierMatches(verifier, sameVerifier), true);
    assert.equal(verifierMatches(verifier, otherPassword), false);
    assert.notEqual(verifier, otherSalt);
});

test('offline verifier rejects malformed salt data', async () => {
    await assert.rejects(
        deriveVerifier('password', 'not-a-salt', 1000, webcrypto),
        /Invalid offline credential data/
    );
});

test('login identifiers normalize email case and phone formatting', () => {
    assert.equal(normalizeLoginIdentifier('  User@Example.COM '), 'user@example.com');
    assert.equal(normalizeLoginIdentifier('+1 (555) 012-3456'), '+15550123456');
});
