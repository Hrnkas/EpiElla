# EpiElla

Gentelella v4 + Epiphany starter framework with JWT auth, MySQL/SQLite, and Docker Compose.

## Prerequisites

- **PHP 8.3+** with extensions: `pdo_sqlite`, `openssl`, `mbstring`, `zip` (and `pdo_mysql` for MySQL profile)
- **Composer** 2.x
- **Node.js 20+** and npm
- **Git** with submodules: `git clone --recurse-submodules https://github.com/Hrnkas/EpiElla.git`

## First-time setup

```bash
cp .env.example .env
# Native dev: set SQLITE_PATH=data/epiella.sqlite in .env (see comments in .env.example)

cd api && composer install && cd ..
php scripts/migrate.php apply
php scripts/seed.php

cd web && npm install && npm run build && cd ..
```

Default login (from `.env`): `admin@localhost` / `changeme`

## Quickstart — Windows / native (one server on :8080)

Serves the **built** frontend and API from a single PHP process (no Docker):

```bat
start-dev.bat
```

Open [http://127.0.0.1:8080/login.html](http://127.0.0.1:8080/login.html)

Override host/port: `set DEV_PORT=9000` before running, or use `start-dev.sh` on macOS/Linux.

**Optional hot reload:** keep `start-dev.bat` running, then in another terminal:

```bash
cd web && npm run dev
```

Vite (port 9173) proxies `/api` to `http://localhost:8080`. Use Vite for UI edits; use `start-dev.bat` alone for a simple all-in-one server.

## Quickstart — Docker (SQLite, default)

```bash
cp .env.example .env
docker compose up --build
```

Open [http://localhost:8080/login.html](http://localhost:8080/login.html)

The API container runs migrations and seeds the admin user on startup.

## MySQL profile

```bash
# In .env:
DB_DRIVER=mysql
DB_HOST=db
DB_NAME=epiella
DB_USER=epiella
DB_PASS=secret
SQLITE_PATH=/var/data/epiella.sqlite

docker compose --profile mysql up --build
```

## Environment variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_DRIVER` | `sqlite` | `sqlite` or `mysql` |
| `DB_HOST` | `db` | MySQL host (Docker service name) |
| `DB_NAME` | `epiella` | Database name |
| `DB_USER` | `epiella` | MySQL user |
| `DB_PASS` | `secret` | MySQL password |
| `DB_PORT` | `3306` | MySQL port |
| `SQLITE_PATH` | see `.env.example` | SQLite path; relative paths resolve from **repo root** |
| `JWT_SECRET` | — | **Required in production** — signing key for JWTs |
| `JWT_ACCESS_TTL` | `3600` | Access token lifetime (seconds) |
| `JWT_REFRESH_TTL` | `2592000` | Refresh token lifetime (seconds, 30 days) |
| `CORS_ORIGINS` | `http://localhost:8080` | Comma-separated allowed origins, or `*` |
| `ADMIN_EMAIL` | `admin@localhost` | Seed admin email |
| `ADMIN_PASSWORD` | `changeme` | Seed admin password |
| `VITE_API_BASE` | *(empty)* | Frontend API base URL for split deploy |

## Architecture

```
Browser → nginx or router.php (static + /api) → Epiphany PHP → SQLite or MariaDB
```

- **Access JWT** (~1h): `sessionStorage`, sent as `Authorization: Bearer`
- **Refresh JWT** (~30d): `localStorage`, exchanged via `POST /api/auth/refresh.json`

Native dev uses [`api/public/router.php`](api/public/router.php) to mirror nginx: static files from `web/dist/`, API via `/api/*`.

## API routes

| Method | Path | Auth |
|--------|------|------|
| POST | `/api/auth/login.json` | Public |
| POST | `/api/auth/refresh.json` | Public |
| POST | `/api/auth/logout.json` | Public |
| GET | `/api/auth/me.json` | Bearer |
| GET/POST | `/api/records.json` | Bearer |
| GET/PUT/DELETE | `/api/records/{id}.json` | Bearer |

## Split deploy (UI and API on different hosts)

```bash
cd web
VITE_API_BASE=https://api.example.com npm run build
```

Set `CORS_ORIGINS=https://app.example.com` on the API server.

When UI and API share a domain (nginx or `router.php`), leave `VITE_API_BASE` empty.

## Adding a migration

1. Create the next numbered file in **both** driver folders:
   ```
   api/migrations/mysql/005_add_example.sql
   api/migrations/sqlite/005_add_example.sql
   ```
2. Write forward-only DDL (`CREATE TABLE`, `ALTER TABLE ADD COLUMN`, etc.).
3. Update the full snapshot in `api/schema/{mysql,sqlite}/schema.sql`.
4. Update PHP repositories/controllers if the API uses new columns.
5. Run locally:
   ```bash
   php scripts/migrate.php status
   php scripts/migrate.php apply
   ```
6. Deploy — the API container entrypoint runs pending migrations automatically.

**Naming:** `NNN_short_description.sql` (zero-padded prefix, one logical change per file).

**SQLite note:** complex schema changes (rename/drop columns) may require a table rebuild migration. Prefer additive changes.

## Project layout

```
EpiElla/
├── api/              Epiphany API (PHP 8.3) — submodule at api/vendor/epiphany
├── web/
│   ├── epiella/      EpiElla overlay (auth, nav, entry script) — you edit this
│   ├── production/   EpiElla HTML pages only
│   └── node_modules/gentelella/   upstream UI kit (npm, never edit)
├── scripts/          migrate.php, seed.php
├── start-dev.bat     Native one-server dev (Windows)
├── start-dev.sh      Native one-server dev (macOS/Linux)
├── nginx/            Reverse proxy config (Docker)
├── UPSTREAM.md       Pinned dependency versions
└── docker-compose.yml
```

## Developer docs

- [docs/DEVELOPER-EN.md](docs/DEVELOPER-EN.md) — English
- [docs/DEVELOPER-DE.md](docs/DEVELOPER-DE.md) — Deutsch
- [docs/updating-gentelella.md](docs/updating-gentelella.md) — bump Gentelella npm package
- [docs/updating-epiphany.md](docs/updating-epiphany.md) — bump Epiphany submodule

## License

EpiElla starter code: MIT. Gentelella and Epiphany retain their upstream licenses.
