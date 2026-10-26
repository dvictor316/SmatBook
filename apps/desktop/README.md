# SmartProbook Desktop

Installable desktop client for Smartprobook. It opens the live POS workspace when connected and uses the POS offline cache and queued-sale synchronization already provided by the web app.

## Local Commands

```bash
cd apps/desktop
npm install
npm run dev
```

Build an installer for the current operating system with:

```bash
npm run build
```

## Windows installer for Business Owners

The Windows installer is built on a Windows GitHub Actions runner. After the workflow is pushed to the repository:

1. Open **Actions** and run **Build SmartProbook Desktop for Windows**.
2. Enter the desktop release version (for example, `0.1.0`).
3. When the workflow completes, download the `.msi` from the GitHub Release named `SmartProbook Desktop v<version> for Windows`.
4. Share that release download link with Windows users. GitHub release assets are publicly downloadable only if this repository is public; for a private repository, upload the MSI to SmartProbook's customer download area instead.

The installer is unsigned unless a Windows code-signing certificate is configured. Windows may show a SmartScreen warning until the application is signed and has established reputation.

## Configuration

The launcher is configured to open the POS at:

```text
https://smartprobook.com/pos
```

To use a different Smartprobook host, update `SMARTPROBOOK_URL` in `shell/index.html` and the POS URL in `src-tauri/src/main.rs`.

## Offline POS

- The first sign-in and product/stock download require an internet connection. Sign in and open the POS on this desktop at least once before relying on offline sales.
- While connected, the POS refreshes product stock and synchronizes queued sales. When the connection drops, the desktop can reopen the last cached POS workspace and queue supported sales.
- Cash and split sales can be queued offline. Wallet, room-charge, and converted-document sales still require an internet connection.
- This is offline support for POS sales and the saved product-stock snapshot, not offline access to every accounting or administration screen.
- Sales are synchronized to the server when connectivity returns. Staff should review any sale the server flags for stock or workspace conflicts.

The desktop app is a client shell; authentication, subscriptions, tenant separation, and device limits continue to be enforced by the Laravel backend.
