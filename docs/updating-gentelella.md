# Updating Gentelella

Gentelella is consumed as an **npm package** (`gentelella@4`). EpiElla customizations live in [`web/epiella/`](../web/epiella/) and are never overwritten by updates.

## Bump Gentelella

```bash
cd web
npm update gentelella
# or pin: npm install gentelella@4.0.3
npm run build
```

Update the version in [UPSTREAM.md](../UPSTREAM.md).

## What you own (do not lose on update)

| Path | Purpose |
|------|---------|
| `web/epiella/main.js` | Entry script — auth gate + Gentelella init |
| `web/epiella/shell-render.js` | NAV, branding, logout link |
| `web/epiella/auth.js` | JWT login / refresh / logout |
| `web/epiella/api-client.js` | Authenticated fetch wrapper |
| `web/epiella/records.js` | Records CRUD page logic |
| `web/production/*.html` | EpiElla pages only (login, dashboard, records) |
| `web/vite.config.js` | API proxy, shell alias, page list |

## How the overlay works

A Vite plugin redirects Gentelella’s `./shell-render.js` imports to [`epiella/shell-render.js`](../web/epiella/shell-render.js), so runtime shell code uses EpiElla nav without editing upstream.

[`epiella/shell-render.js`](../web/epiella/shell-render.js) imports icon SVGs and topbar/footer renderers from the real upstream file via a direct `node_modules` path.

Gentelella modules are resolved through `gentelella/v4/*` and `gentelella/scss/*` aliases in [`vite.config.js`](../web/vite.config.js).

## After updating

1. `npm run build` — fix import errors in `epiella/main.js` if Gentelella renamed modules
2. Smoke-test login, dashboard charts, records CRUD, logout
3. If upstream changed shell HTML structure, merge changes into `epiella/shell-render.js` (compare with `node_modules/gentelella/src/v4/shell-render.js`)

## Adding demo pages from upstream

Copy HTML from `node_modules/gentelella/production/` into `web/production/`, add the filename to `EPIELLA_PAGES` in `vite.config.js`, and point the script tag at `/epiella/main.js`.
