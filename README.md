# Drachme 💰

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)](https://react.dev/)

**Drachme** is a self-hosted app to track net worth, cashflow, bank imports, and investments. 🪙

Your data stays on your machine. There is no telemetry, no third-party analytics, and no cloud account required.

## About this project

This is a **personal hobby project** built for my own needs. I use it day to day to manage my finances, and I am sharing the code in case it helps someone else.

For now, Drachme is meant to stay a **hobby project**:

- It runs as a **small local Docker stack** on your machine (not a hosted SaaS).
- I do **not** plan to ship a native desktop or mobile app at this time.
- Feature scope follows what I actually need; there is no roadmap to match commercial products.

If that fits your use case, you are welcome to try it, fork it, or contribute. See [CONTRIBUTING.md](CONTRIBUTING.md).

> **Not financial advice.** Drachme helps you organize your own records. It is not a regulated financial product.

## Screenshots

| Dashboard | Accounts |
| --- | --- |
| ![Dashboard](.github/images/dashboard.png) | ![Accounts](.github/images/accounts.png) |

| Transactions | Import wizard |
| --- | --- |
| ![Transactions](.github/images/transactions.png) | ![Import](.github/images/import.png) |

| Investments | Settings |
| --- | --- |
| ![Investments](.github/images/investments.png) | ![Settings](.github/images/settings.png) |

## Features

### Accounts and balances 💳

- Account types: checking, savings, investment, credit, loan, credit card, and cash
- Per-user isolation (multi-tenant): each login has its own data space
- Credit card settlement sync with checking accounts
- Loan accounts with payment day, amortization plan, and debt metrics

### Transactions and categories 📊

- Global transaction list with filters and inline category editing
- Manual transaction CRUD
- Category tree and automatic rules (label matching)
- Transaction triage flow with bulk category actions
- Internal transfer detection and linking
- Recurring pattern detection with confirm/dismiss workflow
- Optional field-level encryption for transaction labels and notes (CipherSweet)

### Imports 📥

- Configurable CSV import providers per user
- Column mapping wizard with preview and duplicate handling

### Investments 📈

- Portfolio positions with ISIN support
- Manual price and history refresh
- Market data via **Yahoo Finance** (quotes and ~100-day history)
- ISIN to symbol resolution via **OpenFIGI** (no API key required; optional key improves rate limits)
- Portfolio overview and position detail pages

### Dashboard

- Net worth KPIs and history with date range
- Cashflow chart aligned with transaction list filters
- Account allocation and drill-down

### UX

- Dark theme by default, customizable primary color
- English and French UI
- Built with [shadcn/ui](https://ui.shadcn.com/), Inertia.js, and Tailwind CSS 4

## Tech stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.4, Laravel 13, Fortify |
| Frontend | React 19, Inertia.js, TypeScript (strict) |
| Database | PostgreSQL 16 (Docker) |
| Quality | PHPStan/Larastan level 8, ESLint, PHPUnit |
| Runtime | Docker Compose |

## Quick start

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) with Compose v2
- Git

### Install

```bash
git clone https://github.com/adrien-force/Drachme.git
cd Drachme

make normalize   # line endings (helpful on Windows/WSL)
make check       # verify Docker is available

make bootstrap   # first time only: scaffold Laravel + frontend
make build && make up
make setup       # .env, composer, npm, migrations, assets
```

Open **http://localhost:8080** and register your first user.

Optional hot reload for frontend assets:

```bash
make dev   # Vite on http://localhost:5173
```

### Common commands

```bash
make logs
make migrate
make build-assets   # after PHP route changes (Wayfinder)
make quality        # PHPStan 8 + TypeScript + tests
make down
```

## Configuration

Copy environment defaults from `.env.example` and adjust as needed.

| Variable | Description |
| --- | --- |
| `APP_KEY` | `php artisan key:generate` |
| `CIPHERSWEET_KEY` | Required for encrypted transaction labels: `php artisan ciphersweet:generate-key` |
| `DB_*` | PostgreSQL connection (Docker Compose sets host to `pgsql`) |
| `MARKET_DATA_ENABLED` | Enable/disable market price refresh (default `true`) |
| `MARKET_DATA_CACHE_TTL` | Cache TTL in seconds for quotes |
| `MARKET_DATA_HISTORY_LIMIT` | Days of price history to fetch |
| `OPENFIGI_API_KEY` | Optional. Raises OpenFIGI rate limits |

Market data calls are logged to `storage/logs/laravel.log` (channel `market_data`). Failures never break the refresh action.

## Development

```bash
make shell          # app container
make test
make analyse        # PHPStan level 8
make typecheck      # tsc --noEmit
```

CI runs on push/PR: see [.github/workflows/tests.yml](.github/workflows/tests.yml) and [.github/workflows/lint.yml](.github/workflows/lint.yml).

## Security and privacy

- Self-hosted: you control the server and database backups
- Session-based authentication (email + password)
- Sensitive transaction fields can be encrypted at rest with CipherSweet
- No outbound telemetry from the application

To report a security issue, see [SECURITY.md](SECURITY.md).

## Contributing

Contributions are welcome under the terms of the [AGPL-3.0](LICENSE) license. See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

Copyright (c) Adrien Force

This project is licensed under the **GNU Affero General Public License v3.0 or later**. See [LICENSE](LICENSE).

If you run a modified version as a network service, you must offer corresponding source to users interacting with it over the network.
