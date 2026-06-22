# Upstream dependencies

Pinned versions for EpiElla framework components. Update using the workflows in [docs/updating-gentelella.md](docs/updating-gentelella.md) and [docs/updating-epiphany.md](docs/updating-epiphany.md).

| Component | Source | Pinned version | Integration |
|-----------|--------|----------------|-------------|
| Epiphany | [Hrnkas/epiphany](https://github.com/Hrnkas/epiphany) | git submodule `da10626` | `api/vendor/epiphany` — read-only submodule |
| Gentelella | [ColorlibHQ/gentelella](https://github.com/ColorlibHQ/gentelella) | npm `4.0.2` | `web/node_modules/gentelella` — npm dependency, never edited |
| EpiElla overlay | this repo | — | `web/epiella/` — auth, nav, pages, API client |

Last reviewed: 2026-06-22
