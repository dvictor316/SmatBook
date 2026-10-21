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
        const portrait = await page.evaluate(() => {
            const canvas = document.createElement('canvas'); canvas.width = 1200; canvas.height = 1600;
            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 1200, 1600);
            gradient.addColorStop(0, '#087e8b'); gradient.addColorStop(1, '#d9b26f');
            ctx.fillStyle = gradient; ctx.fillRect(0, 0, 1200, 1600);
            ctx.fillStyle = '#fff'; ctx.font = '70px sans-serif'; ctx.fillText('Portrait room photo', 100, 800);
            return canvas.toDataURL('image/png').split(',')[1];
        });
        await context.route('**/*', async route => {
            const url = new URL(route.request().url());
            if (url.hostname !== 'localhost') return route.abort();
            if (url.pathname === '/') return route.fulfill({ contentType: 'text/html', body: fs.readFileSync(path.join(fixtures, 'room-tour.html')) });
            if (url.pathname.includes('missing.png')) return route.fulfill({ status: 404, body: '' });
            if (url.pathname.endsWith('tour-photo.png')) return route.fulfill({ contentType: 'image/png', body: Buffer.from(portrait, 'base64') });
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
        // Wheel and vertical touch gestures over the image must scroll the surrounding page.
        await page.evaluate(() => { const spacer = document.createElement('div'); spacer.style.height = '1800px'; document.body.append(spacer); });
        await viewport.hover();
        const originalZoom = await page.locator('[data-room-zoom]').textContent();
        await page.mouse.wheel(0, 280);
        await page.waitForFunction(() => scrollY > 50);
        assert.equal(await page.locator('[data-room-zoom]').textContent(), originalZoom, 'normal wheel must not zoom the panorama');
        await page.evaluate(() => scrollTo(0, 0));
        const scrollClient = await context.newCDPSession(page);
        const scrollBounds = await viewport.boundingBox();
        await scrollClient.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [{ x: scrollBounds.x + 100, y: scrollBounds.y + 160, id: 7 }] });
        for (let step = 1; step <= 5; step++) {
            await scrollClient.send('Input.dispatchTouchEvent', { type: 'touchMove', touchPoints: [{ x: scrollBounds.x + 100, y: scrollBounds.y + 160 - step * 22, id: 7 }] });
            await page.waitForTimeout(20);
        }
        await scrollClient.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
        await page.waitForFunction(() => scrollY > 30);
        await page.waitForTimeout(300);
        await page.evaluate(() => scrollTo(0, 0));
        await page.locator('[data-room-action="fit"]').click();
        assert(await page.locator('[data-room-sphere]').isHidden(), 'Fit room must show the complete original image');
        assert.equal(await page.locator('[data-room-zoom]').textContent(), '1×');
        const fit = await viewport.locator('img').evaluate(img => ({ transform: getComputedStyle(img).transform, fit: getComputedStyle(img).objectFit }));
        assert.equal(fit.fit, 'contain');
        assert.equal(fit.transform, 'matrix(1, 0, 0, 1, 0, 0)');
        await page.locator('[data-room-action="panorama"]').click();
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
        await page.locator('[data-room-action="interact"]').click();
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
        await page.locator('[data-room-action="interact"]').click();
        assert.equal(await page.locator('[data-room-action="interact"]').getAttribute('aria-pressed'), 'false');
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
        await play.click();
        await page.waitForFunction(() => document.querySelector('[data-room-mode]').textContent === '3D photo presentation');
        const imageTransform = await viewport.locator('img').evaluate(el => getComputedStyle(el).transform);
        await page.waitForTimeout(350);
        assert.notEqual(await viewport.locator('img').evaluate(el => getComputedStyle(el).transform), imageTransform, 'the 3D photo must actually move');
        for (const width of [320, 800, 1920]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.waitForTimeout(100);
            const coverage = await viewport.evaluate(el => {
                const img = el.querySelector('img');
                const stage = el.getBoundingClientRect(), photo = img.getBoundingClientRect();
                return { fit: getComputedStyle(img).objectFit, stageWidth: stage.width, photoWidth: photo.width, stageHeight: stage.height, photoHeight: photo.height, left: photo.left - stage.left, right: stage.right - photo.right };
            });
            assert.equal(coverage.fit, 'cover');
            assert(coverage.photoWidth >= coverage.stageWidth && coverage.photoHeight >= coverage.stageHeight, 'tour photo must fill the entire viewport');
            assert(coverage.left <= 0 && coverage.right <= 0, 'tour must not leave side panels');
            assert(Number.parseFloat(await page.locator('[data-room-zoom]').textContent()) <= 1.1, 'gentle tour must avoid a heavy extra zoom');
            assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
        }
        await page.locator('[data-room-action="fullscreen"]').click();
        await page.waitForFunction(() => !!document.fullscreenElement);
        await page.waitForTimeout(100);
        assert.equal(await viewport.locator('img').evaluate(el => getComputedStyle(el).objectFit), 'cover');
        await page.screenshot({ path: path.join(fixtures, 'room-photo-tour-fullscreen.png') });
        await page.locator('[data-room-action="fullscreen"]').click();
        await page.waitForFunction(() => !document.fullscreenElement);
        await page.locator('[data-room-action="fit"]').click();
        assert.equal(await viewport.locator('img').evaluate(el => getComputedStyle(el).objectFit), 'contain');
        assert.equal(await play.getAttribute('aria-pressed'), 'false');
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
        await flatPage.emulateMedia({ reducedMotion: 'no-preference' });
        await flatPage.reload();
        await flatPage.waitForFunction(() => document.querySelector('[data-room-mode]').textContent === '3D photo presentation');
        assert.equal(await flatPage.locator('[data-room-action="play"]').getAttribute('aria-pressed'), 'true', 'panorama photos should automatically start a full-width tour');
        await flatPage.locator('[data-room-action="fit"]').click();
        // Match the hotel preview's real Bootstrap + skin cascade on a short tablet screen.
        await flatPage.setViewportSize({ width: 800, height: 500 });
        await flatPage.addStyleTag({ url: 'http://localhost/assets/css/bootstrap.min.css' });
        await flatPage.addStyleTag({ url: 'http://localhost/assets/css/hotel-keto-skin.css' });
        await flatPage.addStyleTag({ url: 'http://localhost/assets/css/hotel-room-viewer.css' });
        await flatPage.evaluate(() => {
            document.body.classList.add('hotel-workspace');
            const viewer = document.querySelector('[data-room-viewer]');
            const modal = document.createElement('div');
            modal.className = 'modal hotel-preview-modal'; modal.style.display = 'block';
            modal.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header">Room preview</div><div class="modal-body"></div></div></div>';
            document.body.append(modal); modal.querySelector('.modal-body').append(viewer);
        });
        const modalBody = flatPage.locator('.modal-body');
        assert(await modalBody.evaluate(el => el.scrollHeight > el.clientHeight), 'short preview must expose a scrollable body');
        await modalBody.hover(); await flatPage.mouse.wheel(0, 300);
        await flatPage.waitForFunction(() => document.querySelector('.modal-body').scrollTop > 10);
        assert.deepEqual(errors, []);
        console.log('Room tour passed: automatic full-width 3D photo tours, portrait/landscape coverage, gentle zoom, page wheel/touch scrolling, complete image fit, short preview scrolling, responsive widths, 360° angles, motion/pause, touch drag/pinch, fullscreen, scene switching, image recovery, reduced motion and WebGL fallback.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
