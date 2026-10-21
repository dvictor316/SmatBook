import { createPanorama } from './room-panorama.js';

export function fittedSize(width, height, imageWidth, imageHeight) {
    if (!width || !height || !imageWidth || !imageHeight) return { width: 0, height: 0 };
    const ratio = Math.min(width / imageWidth, height / imageHeight);
    return { width: imageWidth * ratio, height: imageHeight * ratio };
}

export function constrainView(view, viewport, image) {
    const scale = Math.max(1, Math.min(5, view.scale));
    const fitted = fittedSize(viewport.width, viewport.height, image.width, image.height);
    const maxX = Math.max(0, (fitted.width * scale - viewport.width) / 2);
    const maxY = Math.max(0, (fitted.height * scale - viewport.height) / 2);
    return { scale, x: Math.max(-maxX, Math.min(maxX, view.x)), y: Math.max(-maxY, Math.min(maxY, view.y)) };
}

export function zoomAt(view, scale, point) {
    const next = Math.max(1, Math.min(5, scale));
    const ratio = next / view.scale;
    return { scale: next, x: point.x - (point.x - view.x) * ratio, y: point.y - (point.y - view.y) * ratio };
}

function bindViewer(root) {
    if (root.dataset.viewerBound) return;
    root.dataset.viewerBound = 'true';
    const viewport = root.querySelector('[data-room-viewport]');
    const image = viewport.querySelector('img');
    const canvas = root.querySelector('[data-room-sphere]');
    const label = root.querySelector('[data-room-zoom]');
    const playButton = root.querySelector('[data-room-action="play"]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const pointers = new Map();
    let view = { scale: 1, x: 0, y: 0 };
    let camera = { yaw: 0, pitch: 0, fov: 75 };
    let sphere = null, attempted = false, gesture = null, frame = 0, previousTime = 0, elapsed = 0;
    let playing = root.dataset.panorama === 'true' && !reducedMotion.matches;
    let visible = false, onScreen = true, failed = false;

    const announce = text => { root.querySelector('[data-room-status]').textContent = text; };
    const isPanorama = () => root.dataset.panorama === 'true';
    function point(event) {
        const rect = viewport.getBoundingClientRect();
        return { x: event.clientX - rect.left - rect.width / 2, y: event.clientY - rect.top - rect.height / 2 };
    }
    function render() {
        visible = !!viewport.clientWidth && !!viewport.clientHeight;
        if (!visible || !image.naturalWidth || failed) return;
        if (!attempted && isPanorama()) {
            attempted = true;
            const ratio = image.naturalWidth / image.naturalHeight;
            if (ratio >= 1.95 && ratio <= 2.05) sphere = createPanorama(canvas, image);
        }
        canvas.hidden = !sphere;
        image.hidden = !!sphere;
        root.classList.toggle('is-spherical', !!sphere);
        root.querySelector('[data-room-mode]').textContent = sphere ? '360° immersive tour' : (isPanorama() ? 'Panorama photo tour' : 'Room photo');
        root.querySelector('.room-tour-angles').hidden = !sphere;
        root.querySelector('.room-tour-bearing').hidden = !sphere;
        if (sphere) {
            camera.pitch = Math.max(-80, Math.min(80, camera.pitch));
            camera.fov = Math.max(30, Math.min(100, camera.fov));
            camera.yaw = ((camera.yaw % 360) + 360) % 360;
            sphere.draw(viewport.clientWidth, viewport.clientHeight, camera.yaw, camera.pitch, camera.fov);
            label.textContent = `${(75 / camera.fov).toFixed(1)}×`;
            root.querySelector('[data-room-bearing]').textContent = `${Math.round(camera.yaw) % 360}°`;
        } else {
            view = constrainView(view, { width: viewport.clientWidth, height: viewport.clientHeight }, { width: image.naturalWidth, height: image.naturalHeight });
            image.style.transform = `translate(${view.x}px, ${view.y}px) scale(${view.scale})`;
            label.textContent = `${Number(view.scale.toFixed(1))}×`;
        }
        viewport.classList.toggle('is-zoomed', !!sphere || view.scale > 1);
        root.querySelector('[data-room-action="out"]').disabled = sphere ? camera.fov >= 100 : view.scale <= 1;
        root.querySelector('[data-room-action="in"]').disabled = sphere ? camera.fov <= 30 : view.scale >= 5;
    }
    function updatePlay() {
        playButton.textContent = playing ? 'Pause tour' : 'Play tour';
        playButton.setAttribute('aria-pressed', String(playing));
        root.classList.toggle('is-playing', playing);
        cancelAnimationFrame(frame);
        frame = 0;
        previousTime = 0;
        if (playing && visible && onScreen && !document.hidden && !failed) frame = requestAnimationFrame(tick);
    }
    function stop() { playing = false; updatePlay(); }
    function tick(time) {
        const delta = previousTime ? Math.min((time - previousTime) / 1000, .05) : 0;
        previousTime = time;
        elapsed += delta;
        if (sphere) {
            camera.yaw += delta * 5;
            camera.pitch = Math.sin(elapsed * .15) * 9;
        } else {
            view.scale = 1.8;
            const fitted = fittedSize(viewport.clientWidth, viewport.clientHeight, image.naturalWidth, image.naturalHeight);
            view.x = Math.sin(elapsed * .18) * Math.max(0, (fitted.width * view.scale - viewport.clientWidth) / 2);
            view.y = Math.cos(elapsed * .14) * Math.max(0, (fitted.height * view.scale - viewport.clientHeight) / 2);
        }
        render();
        if (playing && visible && onScreen && !document.hidden && !failed) frame = requestAnimationFrame(tick);
    }
    function reset() {
        view = { scale: 1, x: 0, y: 0 };
        camera = { yaw: 0, pitch: 0, fov: 75 };
        pointers.clear(); gesture = null; elapsed = 0;
        render();
    }
    function zoom(factor, anchor = { x: 0, y: 0 }) {
        if (sphere) camera.fov /= factor;
        else view = zoomAt(view, view.scale * factor, anchor);
        render();
    }
    function rebase() {
        const points = [...pointers.values()];
        gesture = points.length ? { view: { ...view }, camera: { ...camera }, points } : null;
    }
    function loaded() {
        sphere?.destroy(); sphere = null; attempted = false; failed = false;
        playButton.disabled = false;
        root.querySelector('[data-room-loading]').hidden = true;
        reset(); updatePlay();
    }
    root.addEventListener('click', async event => {
        const scene = event.target.closest('[data-room-scene]');
        if (scene) {
            stop(); sphere?.destroy(); sphere = null; attempted = false;
            image.hidden = false; canvas.hidden = true;
            root.querySelector('[data-room-loading]').hidden = false;
            root.dataset.panorama = scene.dataset.panorama;
            root.querySelector('[data-room-caption]').textContent = scene.dataset.caption;
            image.alt = scene.dataset.caption;
            image.src = scene.dataset.src;
            root.querySelectorAll('[data-room-scene]').forEach(item => item.setAttribute('aria-pressed', String(item === scene)));
            announce(`Showing ${scene.dataset.caption}`);
            return;
        }
        const angle = event.target.closest('[data-room-angle]');
        if (angle && sphere) {
            stop(); camera.fov = 75;
            if (angle.dataset.roomAngle === 'up' || angle.dataset.roomAngle === 'down') camera.pitch = angle.dataset.roomAngle === 'up' ? 55 : -55;
            else { camera.yaw = Number(angle.dataset.roomAngle); camera.pitch = 0; }
            render(); announce(`${angle.textContent} view`); return;
        }
        const button = event.target.closest('[data-room-action]');
        if (!button) return;
        const action = button.dataset.roomAction;
        if (action === 'play') { playing = !playing; updatePlay(); return; }
        if (action === 'fullscreen') {
            try {
                if (document.fullscreenElement === root) await document.exitFullscreen();
                else if (root.requestFullscreen) await root.requestFullscreen();
            } catch (_) { announce('Fullscreen is unavailable in this browser. You can continue exploring here.'); }
            return;
        }
        stop();
        if (action === 'fit') { reset(); announce('Room view reset'); }
        else zoom(action === 'in' ? 1.3 : 1 / 1.3);
    });
    viewport.addEventListener('wheel', event => {
        event.preventDefault(); stop();
        zoom(Math.exp(-Math.max(-100, Math.min(100, event.deltaY)) * .004), point(event));
    }, { passive: false });
    viewport.addEventListener('dblclick', event => { stop(); if (sphere || view.scale > 1) reset(); else zoom(2, point(event)); });
    viewport.addEventListener('pointerdown', event => {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        stop(); viewport.setPointerCapture(event.pointerId);
        pointers.set(event.pointerId, point(event)); rebase();
    });
    viewport.addEventListener('pointermove', event => {
        if (!pointers.has(event.pointerId) || !gesture) return;
        pointers.set(event.pointerId, point(event));
        const points = [...pointers.values()];
        if (points.length >= 2 && gesture.points.length >= 2) {
            const [a, b] = points, [initialA, initialB] = gesture.points;
            const initialDistance = Math.hypot(initialB.x - initialA.x, initialB.y - initialA.y);
            if (!initialDistance) return;
            const factor = Math.hypot(b.x - a.x, b.y - a.y) / initialDistance;
            if (sphere) camera.fov = gesture.camera.fov / factor;
            else {
                const anchor = { x: (initialA.x + initialB.x) / 2, y: (initialA.y + initialB.y) / 2 };
                view = zoomAt(gesture.view, gesture.view.scale * factor, anchor);
                view.x += (a.x + b.x) / 2 - anchor.x; view.y += (a.y + b.y) / 2 - anchor.y;
            }
        } else if (sphere) {
            camera.yaw = gesture.camera.yaw - (points[0].x - gesture.points[0].x) * camera.fov / viewport.clientHeight;
            camera.pitch = gesture.camera.pitch + (points[0].y - gesture.points[0].y) * camera.fov / viewport.clientHeight;
        } else if (view.scale > 1) {
            view.x = gesture.view.x + points[0].x - gesture.points[0].x;
            view.y = gesture.view.y + points[0].y - gesture.points[0].y;
        }
        render();
    });
    function release(event) { pointers.delete(event.pointerId); rebase(); }
    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(name => viewport.addEventListener(name, release));
    viewport.addEventListener('keydown', event => {
        if (!['+', '=', '-', 'Home', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', ' '].includes(event.key)) return;
        event.preventDefault();
        if (event.key === ' ') { playing = !playing; updatePlay(); return; }
        stop();
        if (event.key === 'Home') return reset();
        if (['+', '=', '-'].includes(event.key)) return zoom(event.key === '-' ? 1 / 1.3 : 1.3);
        if (sphere) {
            if (event.key === 'ArrowLeft') camera.yaw -= 12;
            if (event.key === 'ArrowRight') camera.yaw += 12;
            if (event.key === 'ArrowUp') camera.pitch += 12;
            if (event.key === 'ArrowDown') camera.pitch -= 12;
        } else {
            if (event.key === 'ArrowLeft') view.x += 40;
            if (event.key === 'ArrowRight') view.x -= 40;
            if (event.key === 'ArrowUp') view.y += 40;
            if (event.key === 'ArrowDown') view.y -= 40;
        }
        render();
    });
    image.addEventListener('load', loaded);
    image.addEventListener('error', () => {
        failed = true; stop(); playButton.disabled = true;
        root.querySelector('[data-room-loading]').hidden = true;
        root.querySelector('[data-room-mode]').textContent = 'Image unavailable';
        announce('This room image could not load. Choose another photo or upload a replacement.');
    });
    canvas.addEventListener('webglcontextlost', event => {
        event.preventDefault(); stop(); sphere?.destroy(); sphere = null; attempted = true; render();
        announce('Switched to photo view. Drag and zoom are still available.');
    });
    const modal = root.closest('.modal');
    modal?.addEventListener('shown.bs.modal', () => { render(); updatePlay(); });
    modal?.addEventListener('hidden.bs.modal', () => { visible = false; cancelAnimationFrame(frame); pointers.clear(); gesture = null; });
    document.addEventListener('visibilitychange', updatePlay);
    document.addEventListener('fullscreenchange', () => {
        root.querySelector('[data-room-action="fullscreen"]').textContent = document.fullscreenElement === root ? 'Exit fullscreen' : 'Fullscreen';
        render(); updatePlay();
    });
    if (!root.requestFullscreen) root.querySelector('[data-room-action="fullscreen"]').hidden = true;
    reducedMotion.addEventListener('change', () => { if (reducedMotion.matches) stop(); });
    new ResizeObserver(() => { render(); updatePlay(); }).observe(viewport);
    new IntersectionObserver(entries => { onScreen = entries[0].isIntersecting; updatePlay(); }).observe(root);
    if (image.complete && image.naturalWidth) loaded();
    else { render(); updatePlay(); }
}

if (typeof document !== 'undefined') {
    const initialize = () => document.querySelectorAll('[data-room-viewer]').forEach(bindViewer);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
}
