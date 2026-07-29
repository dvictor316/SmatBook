# SmartProbook Apps

This folder contains isolated starter apps for SmartProbook clients.

- `mobile`: Expo/React Native shell for Android and iOS.
- `desktop`: Tauri desktop shell for Windows, macOS, and Linux.

Both apps point to the existing SmartProbook web platform by default. Business rules such as tenant separation, paid plan user limits, free custom device limits, and subscription checks remain enforced by the Laravel backend.

Set `SMARTPROBOOK_URL` or the platform-specific public environment variable before building if the production URL changes.
