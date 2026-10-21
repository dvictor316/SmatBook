const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const fixtures = process.env.RESPONSIVE_FIXTURES || path.join(require('node:os').tmpdir(), 'smartprobook-responsive');
const publicDir = path.resolve(__dirname, '../../public');
(async () => {
    const browser = await chromium.launch({ executablePath: process.env.CHROMIUM_EXECUTABLE || undefined, args: ['--enable-unsafe-swiftshader'] });
    try {
        const context = await browser.newContext({ viewport: { width: 1024, height: 1000 }, hasTouch: true, reducedMotion: 'reduce' });
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        // A synthetic calibration panorama: each viewing angle has a different color.
        const panorama = await page.evaluate(() => {
            const canvas = document.createElement('canvas'); canvas.width = 2048; canvas.height = 1024;
            const ctx = canvas.getContext('2d');
            for (let i = 0; i < 8; i++) {
                ctx.fillStyle = `hsl(${i * 45},55%,45%)`; ctx.fillRect(i * 256, 0, 256, 1024);
                ctx.fillStyle = '#ffffff'; ctx.font = '32px sans-serif'; ctx.fillText(`ANGLE ${i * 45}°`, i * 256 + 20, 510);
            }
            return canvas.toDataURL('image/png').split(',')[1];
        });
        await context.route('**/*', async route => {
            const url = new URL(route.request().url());
            if (url.hostname !== 'localhost') return route.abort();
            if (url.pathname === '/') return route.fulfill({ contentType: 'text/html', body: fs.readFileSync(path.join(fixtures, 'room-tour.html')) });
            if (url.pathname.includes('missing.png')) return route.fulfill({ status: 404, body: '' });
            if (url.pathname.startsWith('/storage/')) return route.fulfill({ contentType: 'image/png', body: Buffer.from(panorama, 'base64') });
            const file = path.join(publicDir, url.pathname);
            if (url.pathname.startsWith('/assets/') && fs.existsSync(file)) return route.fulfill({ body: fs.readFileSync(file), contentType: file.endsWith('.js') ? 'application/javascript' : 'text/css' });
            return route.abort();
        });
        await page.goto('http://localhost/');
        await page.waitForFunction(() => document.querySelector('[data-room-mode]').textContent === '360° immersive tour');
        const viewport = page.locator('[data-room-viewport]');
        const play = page.locator('[data-room-action="play"]');
        assert.equal(await play.getAttribute('aria-pressed'), 'false', 'reduced motion must prevent automatic playback');
        for (const width of [320, 390, 800, 1024, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `overflow at ${width}`);
        }
        const frontPixels = await page.locator('[data-room-sphere]').screenshot();
        await page.locator('[data-room-angle="90"]').click();
        assert.notDeepEqual(await page.locator('[data-room-sphere]').screenshot(), frontPixels, 'changing angle must render different panorama pixels');
        assert.equal(await page.locator('[data-room-bearing]').textContent(), '90°');
        await page.locator('[data-room-angle="up"]').click();
        await viewport.focus(); await page.keyboard.press('Home');
        assert.equal(await page.locator('[data-room-bearing]').textContent(), '0°');
        await page.keyboard.press('ArrowRight');
        assert.equal(await page.locator('[data-room-bearing]').textContent(), '12°');
        await play.click(); await page.waitForTimeout(500);
        assert.notEqual(await page.locator('[data-room-bearing]').textContent(), '12°', 'tour must actually move');
        await play.click();
        const pausedBearing = await page.locator('[data-room-bearing]').textContent();
        await page.waitForTimeout(250);
        assert.equal(await page.locator('[data-room-bearing]').textContent(), pausedBearing);
        const bounds = await viewport.boundingBox();
        const client = await context.newCDPSession(page);
        const touch = (type, points) => client.send('Input.dispatchTouchEvent', { type, touchPoints: points });
        await touch('touchStart', [{ x: bounds.x + 200, y: bounds.y + 150, id: 1 }]);
        await touch('touchMove', [{ x: bounds.x + 300, y: bounds.y + 150, id: 1 }]);
        await touch('touchEnd', []);
        assert.notEqual(await page.locator('[data-room-bearing]').textContent(), pausedBearing, 'touch drag must change angle');
        const oldZoom = await page.locator('[data-room-zoom]').textContent();
        await touch('touchStart', [{ x: bounds.x + 200, y: bounds.y + 150, id: 1 }, { x: bounds.x + 300, y: bounds.y + 150, id: 2 }]);
        await touch('touchMove', [{ x: bounds.x + 160, y: bounds.y + 150, id: 1 }, { x: bounds.x + 340, y: bounds.y + 150, id: 2 }]);
        await touch('touchEnd', []);
        assert.notEqual(await page.locator('[data-room-zoom]').textContent(), oldZoom, 'pinch must zoom');
        await page.locator('[data-room-action="fullscreen"]').click();
        await page.waitForFunction(() => !!document.fullscreenElement);
        await page.locator('[data-room-action="fullscreen"]').click();
        await page.waitForFunction(() => !document.fullscreenElement);
        await page.setViewportSize({ width: 800, height: 1000 });
        await page.screenshot({ path: path.join(fixtures, 'room-tour-tablet.png') });
        await page.locator('[data-room-scene]').nth(1).click();
        await page.waitForFunction(() => document.querySelector('[data-room-mode]').textContent === 'Room photo');
        assert(await page.locator('[data-room-sphere]').isHidden());
        await page.locator('[data-room-action="in"]').click();
        assert.equal(await page.locator('[data-room-zoom]').textContent(), '1.3×');
        await page.locator('[data-room-scene]').nth(2).click();
        await page.waitForFunction(() => document.querySelector('[data-room-mode]').textContent === 'Image unavailable');
        assert(await play.isDisabled());
        await page.locator('[data-room-scene]').first().click();
        await page.waitForFunction(() => document.querySelector('[data-room-mode]').textContent === '360° immersive tour');
        assert(!await play.isDisabled());
        await context.close();
        // Hardware acceleration fallback must still expose the full original image and working controls.
        const fallback = await browser.newContext({ reducedMotion: 'reduce' });
        await fallback.addInitScript(() => { const original = HTMLCanvasElement.prototype.getContext; HTMLCanvasElement.prototype.getContext = function(type, ...args) { return type === 'webgl' ? null : original.call(this, type, ...args); }; });
        await fallback.route('**/*', async route => {
            const url = new URL(route.request().url());
            if (url.pathname === '/') return route.fulfill({ contentType: 'text/html', body: fs.readFileSync(path.join(fixtures, 'room-tour.html')) });
            if (url.pathname.startsWith('/storage/')) return route.fulfill({ contentType: 'image/png', body: Buffer.from(panorama, 'base64') });
            const file = path.join(publicDir, url.pathname);
            if (url.pathname.startsWith('/assets/') && fs.existsSync(file)) return route.fulfill({ body: fs.readFileSync(file), contentType: file.endsWith('.js') ? 'application/javascript' : 'text/css' });
            return route.abort();
        });
        const flatPage = await fallback.newPage();
        await flatPage.goto('http://localhost/');
        await flatPage.waitForFunction(() => document.querySelector('[data-room-mode]').textContent === 'Panorama photo tour');
        await flatPage.locator('[data-room-action="in"]').click();
        assert.equal(await flatPage.locator('[data-room-zoom]').textContent(), '1.3×');
        await flatPage.locator('[data-room-action="fit"]').click();
        assert.equal(await flatPage.locator('[data-room-zoom]').textContent(), '1×');
        assert.deepEqual(errors, []);
        console.log('Room tour passed: responsive widths, 360° angles, motion/pause, touch drag/pinch, fullscreen, scene switching, image recovery, reduced motion and WebGL fallback.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
