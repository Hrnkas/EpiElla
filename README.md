# EpiElla

Gentelella v4 + Epiphany starter framework with JWT auth, MySQL/SQLite, and Docker Compose.

## Quickstart (Docker — SQLite, default)

```bash
cp .env.example .env
docker compose up --build
```

Open [http://localhost:8080/login.html](http://localhost:8080/login.html)

- **Email:** `admin@localhost`
- **Password:** `changeme` (or your `ADMIN_PASSWORD` from `.env`)

The API container runs migrations and seeds the admin user on startup.

## Quickstart (local dev)

**Requirements:** PHP 8.3+, Composer, Node 20+

```bash
cp .env.example .env

# API
cd api && composer install && cd ..
php scripts/migrate.php apply
php scripts/seed.php

# Web (separate terminal)
cd web && npm install && npm run dev
```

Use PHP's built-in server for the API (or point nginx at `api/public`):

```bash
cd api/public
php -S localhost:8081
```

Set `API_URL=http://localhost:8081` when running Vite so `/api` proxies correctly.

## MySQL profile

```bash
# In .env:
DB_DRIVER=mysql
DB_HOST=db
DB_NAME=epiella
DB_USER=epiella
DB_PASS=secret

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
| `SQLITE_PATH` | `/var/data/epiella.sqlite` | SQLite file path |
| `JWT_SECRET` | — | **Required in production** — signing key for JWTs |
| `JWT_ACCESS_TTL` | `3600` | Access token lifetime (seconds) |
| `JWT_REFRESH_TTL` | `2592000` | Refresh token lifetime (seconds, 30 days) |
| `CORS_ORIGINS` | `http://localhost:8080` | Comma-separated allowed origins, or `*` |
| `ADMIN_EMAIL` | `admin@localhost` | Seed admin email |
| `ADMIN_PASSWORD` | `changeme` | Seed admin password |
| `VITE_API_BASE` | *(empty)* | Frontend API base URL for split deploy |

## Architecture

```
Browser → nginx (static + /api proxy) → PHP-FPM (Epiphany) → SQLite or MariaDB
```

- **Access JWT** (~1h): `sessionStorage`, sent as `Authorization: Bearer`
- **Refresh JWT** (~30d): `localStorage`, exchanged via `POST /api/auth/refresh.json`

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

Build the frontend with the API URL baked in:

```bash
cd web
VITE_API_BASE=https://api.example.com npm run build
```

Set `CORS_ORIGINS=https://app.example.com` on the API server.

When UI and API share a domain (nginx serves both), leave `VITE_API_BASE` empty.

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
├── nginx/            Reverse proxy config
├── UPSTREAM.md       Pinned dependency versions
└── docker-compose.yml
```

See [docs/updating-gentelella.md](docs/updating-gentelella.md) and [docs/updating-epiphany.md](docs/updating-epiphany.md) for upgrade workflows.

## License

EpiElla starter code: MIT. Gentelella and Epiphany retain their upstream licenses.
