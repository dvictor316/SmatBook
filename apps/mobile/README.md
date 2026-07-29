# SmartProbook Mobile

Expo starter app for SmartProbook.

## Local Commands

```bash
cd apps/mobile
npm install
npm start
```

## Configuration

By default the app opens:

```text
https://smartprobook.com
```

To point to another host:

```bash
EXPO_PUBLIC_SMARTPROBOOK_URL=https://your-domain.com npm start
```

All authentication, subscription checks, tenant separation, and device-session restrictions are handled by the Laravel backend.
