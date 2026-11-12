(function () {
    var whiteClass = 'spb-auto-contrast-white';
    var resetClass = 'spb-auto-contrast-reset';
    var originalColorProperty = '--spb-contrast-original-color';
    var whiteTextMaxLuminance = 0.179;
    var buttonWhiteTextMaxLuminance = 0.4;
    var scanScheduled = false;
    var observer;

    function parseColor(value) {
        var match = value.match(/^rgba?\((.+)\)$/i);
        if (!match) return null;

        var parts = match[1].split(/[,\s/]+/).filter(Boolean);
        if (parts.length < 3) return null;

        var channels = parts.slice(0, 3).map(function (part) {
            var value = parseFloat(part);
            return part.indexOf('%') !== -1 ? value * 2.55 : value;
        });
        var alpha = parts.length > 3 ? parseFloat(parts[3]) : 1;
        if (parts.length > 3 && parts[3].indexOf('%') !== -1) alpha /= 100;

        return {
            red: channels[0],
            green: channels[1],
            blue: channels[2],
            alpha: Math.max(0, Math.min(1, alpha))
        };
    }

    function getBackgroundLuminance(element) {
        var ancestors = [];
        for (var node = element; node; node = node.parentElement) {
            ancestors.push(node);
        }

        var background = [255, 255, 255];
        for (var index = ancestors.length - 1; index >= 0; index--) {
            var color = parseColor(window.getComputedStyle(ancestors[index]).backgroundColor);
            if (!color || color.alpha === 0) continue;

            background = [
                color.red * color.alpha + background[0] * (1 - color.alpha),
                color.green * color.alpha + background[1] * (1 - color.alpha),
                color.blue * color.alpha + background[2] * (1 - color.alpha)
            ];
        }

        var linear = background.map(function (channel) {
            channel /= 255;
            return channel <= 0.04045 ? channel / 12.92 : Math.pow((channel + 0.055) / 1.055, 2.4);
        });

        return linear[0] * 0.2126 + linear[1] * 0.7152 + linear[2] * 0.0722;
    }

    function isFontAwesomeIcon(element) {
        return Array.prototype.some.call(element.classList, function (className) {
            return /^fa(?:$|-)|^(?:fas|far|fal|fab|fad|fat|fass|svg-inline--fa|bi(?:-|$)|material-icons?|material-symbols-outlined|feather(?:-|$)|lucide(?:-|$))$/.test(className)
                || className === 'icon'
                || className === 'icons';
        });
    }

    function isButtonLike(element) {
        if (/^(BUTTON|INPUT)$/.test(element.tagName) || element.getAttribute('role') === 'button') return true;
        if (Array.prototype.some.call(element.classList, function (className) {
            return /(^|-)btn(?:-|$)|(^|-)button(?:-|$)/.test(className);
        })) return true;

        if (element.tagName !== 'A') return false;
        var background = parseColor(window.getComputedStyle(element).backgroundColor);
        return background !== null && background.alpha > 0;
    }

    function hasButtonLikeAncestor(element) {
        for (var node = element; node; node = node.parentElement) {
            if (isButtonLike(node)) return true;
        }

        return false;
    }

    function hasVisibleText(element) {
        return Array.prototype.some.call(element.childNodes, function (node) {
            return node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '';
        });
    }

    function isTextElement(element) {
        if (/^(SCRIPT|STYLE|TEMPLATE|NOSCRIPT)$/.test(element.tagName)) return false;
        return hasVisibleText(element)
            || /^(BUTTON|INPUT|OPTION|SELECT|TEXTAREA)$/.test(element.tagName)
            || element.tagName === 'SVG'
            || isFontAwesomeIcon(element);
    }

    function scan() {
        scanScheduled = false;
        if (!document.body) return;

        observer.disconnect();
        var managedElements = document.querySelectorAll('.' + whiteClass + ', .' + resetClass);
        Array.prototype.forEach.call(managedElements, function (element) {
            element.classList.remove(whiteClass, resetClass);
            element.style.removeProperty(originalColorProperty);
        });

        var results = [];
        var darkTextElements = new Set();
        Array.prototype.forEach.call(document.body.querySelectorAll('*'), function (element) {
            if (!isTextElement(element)) return;

            var whiteTextThreshold = hasButtonLikeAncestor(element)
                ? buttonWhiteTextMaxLuminance
                : whiteTextMaxLuminance;
            var darkBackground = getBackgroundLuminance(element) <= whiteTextThreshold;
            var result = {
                element: element,
                darkBackground: darkBackground,
                originalColor: window.getComputedStyle(element).color
            };
            results.push(result);
            if (darkBackground) darkTextElements.add(element);
        });

        results.forEach(function (result) {
            if (result.darkBackground) {
                result.element.classList.add(whiteClass);
                return;
            }

            for (var ancestor = result.element.parentElement; ancestor; ancestor = ancestor.parentElement) {
                if (!darkTextElements.has(ancestor)) continue;
                result.element.style.setProperty(originalColorProperty, result.originalColor);
                result.element.classList.add(resetClass);
                break;
            }
        });

        observer.observe(document.documentElement, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ['class', 'style', 'data-layout-mode']
        });
    }

    function scheduleScan() {
        if (scanScheduled) return;
        scanScheduled = true;
        window.requestAnimationFrame(scan);
    }

    function initialize() {
        observer = new MutationObserver(scheduleScan);
        scan();
        document.addEventListener('smartprobook:page-loaded', scheduleScan);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
