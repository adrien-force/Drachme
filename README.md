# Drachme

Application locale de gestion de patrimoine et de dépenses — alternative personnelle à Finary (vue patrimoine) et Microsoft Money (catégorisation).

## Stack

| Couche   | Technologie                                        |
| -------- | -------------------------------------------------- |
| Backend  | PHP 8.4 (Docker), **Laravel 13**                   |
| Frontend | React 19, Inertia.js, **TypeScript strict**, Tailwind 4 |
| UI       | shadcn/ui, thème sombre, effet glass ([UI-DESIGN](docs/UI-DESIGN.md)) |
| Qualité  | PHPStan + Larastan **niveau 8**, Pint, ESLint      |
| Base     | PostgreSQL 16                                      |
| Runtime  | Docker Compose (WSL2 + Docker Desktop)             |

## Documentation

- **[Contrat de développement](docs/DEVELOPMENT_CONTRACT.md)** — normes obligatoires
- **[Setup Windows + WSL](docs/SETUP-WINDOWS-WSL.md)** — environnement recommandé
- [Cahier des charges](docs/CAHIER-DES-CHARGES.md) · [Architecture](docs/ARCHITECTURE.md) · [Master TODO](docs/MASTER-TODO.md)
- Tâches détaillées : `docs/subs/SUB-*.md`

## Premier démarrage (WSL)

Code canonique : `/home/adrie/Projects/Drachme` — ouvrir dans Cursor via `\\wsl$\Ubuntu\home\adrie\Projects\Drachme`.

```bash
cd ~/Projects/Drachme
make normalize          # CRLF → LF (Windows/WSL)
make check

make bootstrap          # Laravel 13 + react-starter-kit (une seule fois)
make build && make up   # Docker : nginx, PHP, Postgres
make setup              # .env, composer, build Vite, migrations

make dev                # optionnel : Vite HMR sur :5173
make quality            # PHPStan 8 + TypeScript + tests
```

| URL | Service |
|-----|---------|
| http://localhost:8080 | Application (nginx) |
| http://localhost:5173 | Vite dev (`make dev`) |

> Ne pas développer au quotidien sur `C:\Users\adrie\Projects\Drachme` (lent sous `/mnt/c/`).

### Dépannage rapide

| Problème | Action |
|----------|--------|
| HTTP 404 sur :8080 | `chmod 755 ~/Projects/Drachme` puis `make setup` |
| Page sans styles | `make build-assets` |
| `APP_KEY` vide | `make key` |
| Voir [SETUP-WINDOWS-WSL.md](docs/SETUP-WINDOWS-WSL.md) | Docker, ports, CRLF |

## Commandes utiles

```bash
make up / make down / make logs
make migrate
make build-assets      # après changement de routes PHP (Wayfinder)
make quality
```

## Ordre de build V1

Voir [docs/MASTER-TODO.md](docs/MASTER-TODO.md) — infra (SUB-00) → dashboard factice (SUB-41) → comptes → …

## Licence

Projet personnel — à définir.
