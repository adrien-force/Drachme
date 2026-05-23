# Security Policy

## Supported versions

Security fixes are applied to the `main` branch. There are no long-term release branches at this time.

## Reporting a vulnerability

**Please do not open a public GitHub issue for security vulnerabilities.**

Send a private report with:

- Description of the issue and impact
- Steps to reproduce
- Affected version or commit hash
- Suggested fix (if any)

Contact: open a [GitHub Security Advisory](https://github.com/adrien-force/Drachme/security/advisories/new) or email the repository owner via their GitHub profile.

You should receive a response within a reasonable timeframe (typically within 14 days).

## Scope

In scope:

- Authentication and session handling
- Multi-tenant data isolation between users
- SQL injection, XSS, CSRF in the web application
- Insecure defaults in shipped configuration examples

Out of scope:

- Denial of service against a self-hosted instance you control
- Issues in third-party services (Yahoo Finance, OpenFIGI) or their availability
- Vulnerabilities in dependencies already fixed in a newer upstream release (please still report so we can bump versions)

## Safe deployment practices

- Keep `APP_DEBUG=false` in production
- Use strong database credentials and restrict network access to PostgreSQL
- Back up `storage/` and your database regularly
- Never commit `.env` or encryption keys to version control
