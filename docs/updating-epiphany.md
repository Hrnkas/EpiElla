# Updating Epiphany

Epiphany is a **git submodule** at [`api/vendor/epiphany`](../api/vendor/epiphany). EpiElla code lives outside the submodule.

## Bump Epiphany

```bash
cd api/vendor/epiphany
git fetch origin
git log HEAD..origin/master --oneline
git checkout <new-commit>
cd ../../..
git add api/vendor/epiphany
php scripts/migrate.php status   # after composer install
```

Update the commit SHA in [UPSTREAM.md](../UPSTREAM.md).

## Integration touchpoints

- [`api/public/index.php`](../api/public/index.php) — bootstrap and routes
- [`scripts/migrate.php`](../scripts/migrate.php), [`scripts/seed.php`](../scripts/seed.php)
- [`api/lib/DatabaseFactory.php`](../api/lib/DatabaseFactory.php) — MySQL via `EpiDatabase`; SQLite via EpiElla `SqliteDatabase`

## Rules

- **Never patch files inside `api/vendor/epiphany`.** Fix your fork ([Hrnkas/epiphany](https://github.com/Hrnkas/epiphany)) and bump the submodule.
- Run auth + records API smoke tests after every bump.
