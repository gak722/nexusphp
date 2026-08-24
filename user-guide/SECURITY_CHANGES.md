# Security Changes & Developer Guidance

This document summarizes recent security- and operations-focused changes in the NexusPHP framework and lists the actions application authors should take when building apps on top of it.

Summary of changes
- Centralized session cookie parameters in `framework/Session/SessionManager.php` (Secure, HttpOnly, SameSite=Lax). CLI tests fall back to an in-memory `$_SESSION` to avoid warnings.
- Cache drivers now use a JSON envelope format and include a migration helper script: `scripts/migrate_cache_to_json.php`.
- Queue drivers store structured failure payloads and support delayed jobs; workers and DLQ handling were hardened.
- HTTP header and cookie setters validate against CRLF injection and enforce safe lengths.
- JWT handling enforces `iat` with configurable leeway and validates keys.
- CI/SAST scaffolding added: `.github/workflows/ci.yml`, `codeql-analysis.yml`, and `.github/dependabot.yml`.
- Documentation and runbooks added: `docs/runbooks/queue_runbook.md`, `docs/security/*`.

What application developers must do
- Secrets: Do not store `APP_KEY` or JWT secrets in source or .env files in production. Use a vault (HashiCorp Vault, AWS Secrets Manager, etc.) and configure your deployment to inject secrets into the runtime environment. See `docs/security/vault_integration.md`.
- Cache migration: If you have existing serialized cache files, run the migration dry-run on staging and then apply it after backups. Example (inside app root):

```bash
php scripts/migrate_cache_to_json.php --path=storage/framework/cache
# apply (requires operator approval):
MIGRATE_CACHE_ALLOW=true php scripts/migrate_cache_to_json.php --path=storage/framework/cache --apply
```

- Session behavior: The framework now sets secure cookie attributes centrally. Ensure your login/logout flows call `session_regenerate_id(true)` when user authentication state changes.
- CI & SCA: Enable the provided GitHub Actions workflows and add repository secrets (VAULT_ADDR, VAULT_ROLE_ID, VAULT_SECRET_ID) to enable secret fetch during CI if you use Vault. Protect `main` with required checks before merge.
- Pen testing: Prioritize tests for deserialization (`unserialize()`), JWT verification, header injection, and session fixation. Add regression tests for any fixes.

Operational runbook highlights
- Queue DLQ: See `docs/runbooks/queue_runbook.md` for immediate remediation and rollback steps for cache migrations.
- Backups: Always snapshot and checksum cache directories and databases before running migrations.

Files added or modified of interest
- `framework/Session/SessionManager.php` — session cookie centralization
- `framework/Cache/*` — JSON envelope changes
- `scripts/migrate_cache_to_json.php` — migration helper
- `.github/workflows/ci.yml`, `codeql-analysis.yml`, `.github/dependabot.yml` — CI/SCA
- `docs/security/*` and `docs/runbooks/*` — operational guidance

Status
- These changes are scoped to the framework and the application repository that uses it. Application authors should follow the steps above before promoting to production.

If you want, I will push these docs and open a PR now.
