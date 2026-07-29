# SmartProbook Desktop

Tauri starter app for SmartProbook desktop.

## Local Commands

```bash
cd apps/desktop
npm install
npm run dev
```

## Configuration

By default the desktop app opens:

```text
https://smartprobook.com
```

To point to another host:

Update `SMARTPROBOOK_URL` in `shell/index.html`.

The desktop app is only a client shell. Authentication, subscription checks, tenant separation, paid custom user limits, and free custom one-device restrictions remain enforced by the Laravel backend.
