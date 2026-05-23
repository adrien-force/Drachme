# Contributing to Drachme

Thank you for your interest in contributing.

## License

By contributing, you agree that your contributions will be licensed under the [GNU Affero General Public License v3.0 or later](LICENSE).

## Before you start

1. Open an issue for large changes so we can align on approach.
2. Fork the repository and work on a feature branch.
3. Keep pull requests focused: one feature or fix per PR.

## Development setup

See [README.md](README.md#quick-start). Minimum check before submitting:

```bash
make quality
```

This runs PHPStan level 8, TypeScript strict check, ESLint, and PHPUnit.

## Code standards

- PHP: `declare(strict_types=1);`, PSR-12 via Laravel Pint, PHPStan level 8
- TypeScript: strict mode, Angular-style naming for components and types
- Commits: [Conventional Commits](https://www.conventionalcommits.org/) (e.g. `feat(accounts): add export`)

## Secrets

Never commit:

- `.env` or real API keys
- Database dumps with personal data
- `CIPHERSWEET_KEY` or production `APP_KEY`

## Pull request checklist

- [ ] `make quality` passes
- [ ] Tests added or updated for behavior changes
- [ ] README updated if user-facing behavior or configuration changed
- [ ] No personal paths, internal ticket IDs, or private docs in the diff
