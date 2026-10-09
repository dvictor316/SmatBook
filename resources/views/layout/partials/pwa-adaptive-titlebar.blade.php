<meta name="theme-color" content="#ffffff" id="spbAdaptiveThemeColor">
<script>
    (function () {
        const meta = document.getElementById('spbAdaptiveThemeColor');
        if (!meta) return;

        const parseColor = (value) => {
            const match = String(value || '').match(/rgba?\(\s*(\d+)\D+(\d+)\D+(\d+)(?:\D+([\d.]+))?/i);
            if (!match) return null;
            return {
                red: Number(match[1]),
                green: Number(match[2]),
                blue: Number(match[3]),
                alpha: match[4] === undefined ? 1 : Number(match[4]),
            };
        };

        const visibleBackground = (element) => {
            let current = element;
            while (current && current !== document) {
                const color = getComputedStyle(current).backgroundColor;
                const parsed = parseColor(color);
                if (parsed && parsed.alpha > 0.08) return { color, ...parsed };
                current = current.parentElement;
            }
            return null;
        };

        const applyThemeColor = () => {
            const sampleX = Math.max(1, window.innerWidth - 8);
            const sample = document.elementFromPoint(sampleX, 2) || document.body;
            const background = visibleBackground(sample);

            if (!background) {
                meta.content = '#ffffff';
                return;
            }

            const luminance = (0.2126 * background.red + 0.7152 * background.green + 0.0722 * background.blue) / 255;
            const isRedSurface = background.red > background.green * 1.22
                && background.red > background.blue * 1.12;

            meta.content = (isRedSurface || luminance < 0.48) ? background.color : '#ffffff';
        };

        const scheduleUpdate = () => window.requestAnimationFrame(applyThemeColor);
        document.addEventListener('DOMContentLoaded', scheduleUpdate, { once: true });
        window.addEventListener('load', scheduleUpdate, { once: true });
        window.addEventListener('resize', scheduleUpdate, { passive: true });
        navigator.windowControlsOverlay?.addEventListener('geometrychange', scheduleUpdate);
    })();
</script>
