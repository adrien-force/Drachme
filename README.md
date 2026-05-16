# Drachme

Application locale de gestion de patrimoine et de dépenses — alternative personnelle à Finary (vue patrimoine) et Microsoft Money (catégorisation).

## Stack


| Couche   | Technologie                                        |
| -------- | -------------------------------------------------- |
| Backend  | PHP 8.3+, Laravel 11+                              |
| Frontend | React 18+, Inertia.js, **TypeScript strict**, Tailwind |
| Qualité  | PHPStan + Larastan **niveau 8**, Pint, ESLint      |
| Base     | PostgreSQL 16                                      |
| Runtime  | Docker Compose (recommandé sur Windows)            |


## Documentation

- **[Contrat de développement](docs/DEVELOPMENT_CONTRACT.md)** — normes obligatoires (PHPStan 8, TS strict, nommage, workflow commit)
- [Cahier des charges](docs/CAHIER-DES-CHARGES.md) — vision, décisions produit, hors scope
- [Architecture](docs/ARCHITECTURE.md) — stack, multi-tenant, conventions
- [Master TODO](docs/MASTER-TODO.md) — plan de build et liens vers les subs

Chaque tâche détaillée : `docs/subs/SUB-*.md`.

## Développement (Windows)

### Option A — Docker (recommandé)

À venir dans `SUB-00-infra` : `docker compose up` lance PHP, Postgres, Vite.

### Option B — WSL2

Si tu préfères le workflow macOS-like :

1. Installer [WSL2](https://learn.microsoft.com/fr-fr/windows/wsl/install) + Ubuntu.
2. Cloner ou monter le repo : `\\wsl$\Ubuntu\home\<user>\Projects\Drachme` ou travailler directement dans le filesystem Linux (`~/Projects/Drachme`).
3. Docker Desktop avec backend WSL2 pour les conteneurs.

Le code vit sous `C:\Users\adrie\Projects\Drachme` ; les commandes `php`, `composer`, `npm` tournent dans le conteneur ou WSL, pas besoin d’installer PHP sur Windows.

## Ordre de build V1

1. Infra + Inertia shell
2. Dashboard (données factices)
3. Comptes
4. Providers + import CSV
5. Transactions, catégories, investissements
6. Moteur de soldes réels + dashboards live

Voir [docs/MASTER-TODO.md](docs/MASTER-TODO.md).

## Licence

Projet personnel — à définir.