# Responsive navigation checks

These checks render public pages and authenticated agent/manager dashboard layouts using an in-memory SQLite test database. They do not access the production database or send payouts. Playwright intercepts requests and serves the fixtures and repository assets locally; external downloads are blocked during browser tests.

Run from the project root with PHP, SQLite and Node installed:

```bash
npm install --prefix /tmp/smat-browser-tests --no-package-lock --no-audit --no-fund playwright
node /tmp/smat-browser-tests/node_modules/playwright/cli.js install chromium
php tests/Browser/render-responsive-pages.php
PLAYWRIGHT_MODULE=/tmp/smat-browser-tests/node_modules/playwright node tests/Browser/responsive-navigation.cjs
```

Set `CHROMIUM_EXECUTABLE` if using an existing Chromium binary. `RESPONSIVE_FIXTURES` overrides the default temporary fixture and screenshot directory.

The matrix covers 320, 360, 390, 600, 768, 800, 820, 991, 992, 1024, 1180, 1199, 1200, 1280, 1440 and 1920 CSS pixels. It checks horizontal page overflow, touch opening/closing, menu position, Escape, height-only viewport changes, orientation/breakpoint transitions, desktop sidebar collapse, and denied localStorage access. Screenshots are saved for the 800px tablet and 390px phone layouts.

Chromium touch emulation is a regression check, not a replacement for testing a physical Android tablet. After deployment, refresh the tablet browser and verify both the public menu and signed-in drawer in portrait and landscape.

Room panorama regression checks use generated calibration images, with no external media or live database:

```bash
php tests/Browser/render-room-tour.php
PLAYWRIGHT_MODULE=/tmp/smat-browser-tests/node_modules/playwright node tests/Browser/room-tour.cjs
```

This checks actual 360° rendering, five responsive widths, angle shortcuts, automatic motion and pause, touch drag and pinch, fullscreen, gallery switching, failed image recovery, reduced motion and photo fallback without WebGL. Set the same `CHROMIUM_EXECUTABLE` and `RESPONSIVE_FIXTURES` overrides as above when needed.
