# Deployment — GitHub Actions → Hostinger

## 1. Flow (spec §48)

```
push to main
  -> GitHub Actions: test job
       - composer install (with dev deps)
       - php -l syntax check on all changed files
       - PHPUnit
       - static checks (php -l across app/, composer validate)
  -> build job (only if test job passed)
       - composer install --no-dev --optimize-autoloader --classmap-authoritative
       - assemble production artifact (see §3 exclude list)
       - upload artifact
  -> deploy job (only on main, only if build passed)
       - rsync/scp artifact over SSH to a new release directory on Hostinger
       - symlink writable/, public/uploads, .env from the persistent shared location
       - run migrations (php spark migrate)
       - atomically flip the "current" symlink to the new release
       - clear/rebuild framework caches
       - health check against a known endpoint
       - on health check failure: flip the symlink back to the previous release
  -> notify (Actions summary; optionally Slack/email on failure)
```

## 2. GitHub Actions secrets required

| Secret | Purpose |
|---|---|
| `HOSTINGER_SSH_HOST` | deploy target host |
| `HOSTINGER_SSH_PORT` | usually 65002 on Hostinger shared hosting |
| `HOSTINGER_SSH_USER` | SFTP/SSH username |
| `HOSTINGER_SSH_KEY` | private key (deploy-only key, not a personal key) |
| `HOSTINGER_DEPLOY_PATH` | absolute path to the releases root |
| `APP_ENV_PRODUCTION` | full contents of the production `.env` (written to the server once, never overwritten by a plain redeploy — see §5) |

None of these are ever echoed to logs; Actions automatically masks secret
values, and workflow steps avoid `set -x` around any step that touches them.

## 3. What ships vs. what's excluded

Ships in the deploy artifact:
```
app/ public/ writable/.gitkeep + writable/*/.gitkeep (empty dirs only)
vendor/ (production-only, built in CI) composer.json composer.lock
```

Excluded from the deploy artifact (spec §49, §52):
```
.git/ .github/ tests/ docs/ node_modules/ (none exist — no Node build step)
*.md at repo root (README stays in the repo, not deployed)
.env, .env.* (secrets are provisioned on the server directly, §5)
phpunit.xml, .phpunit.cache/
IDE files (.vscode/, .idea/)
writable/uploads/*, writable/cache/*, writable/logs/*, writable/session/*
  (real content — never overwritten by a deploy, see §4)
```

`.gitignore` at the repo root already excludes `vendor/`, `.env`,
`writable/*` (except `.gitkeep`s) — the build job's `composer install` and
directory scaffolding recreate what's needed inside the CI runner, so
nothing is missing from what gets rsynced.

## 4. Release strategy (spec §50) — inode-bounded

```
{DEPLOY_PATH}/
├── releases/
│   ├── 2026-09-02-153000/     <- current
│   └── 2026-08-28-091500/     <- previous (rollback target only)
├── shared/
│   ├── .env
│   ├── writable/
│   │   ├── uploads/
│   │   ├── cache/
│   │   ├── logs/
│   │   ├── session/
│   │   └── backup_tmp/
│   └── public/uploads -> ../shared/writable/uploads   (if a symlink, or
│         proxied via a controller route where the host disallows symlinks
│         inside the public docroot)
└── current -> releases/2026-09-02-153000/
```

Deploy steps: upload new release directory → symlink `shared/.env` into it
→ symlink `shared/writable` into it → run `php spark migrate` against the
new release (points at the same DB, so this is safe pre-cutover) → flip
`current` symlink → health check → **delete any release directory beyond
the newest 2**, so the releases directory never accumulates and never
becomes an inode problem (architecture.md §7 rule 6). Apache's document
root points at `{DEPLOY_PATH}/current/public`.

Because `.env` and `writable/` live in `shared/` outside every release
directory, uploads, sessions, cache, and logs survive every deploy
automatically (spec §49's "NEVER delete user-uploaded media during normal
code deployment" and "NEVER overwrite production .env with repository
content" are structural guarantees here, not just process discipline).

## 5. Environment configuration

- `.env` is created **once**, manually, directly on the server (or via a
  one-time secure copy from the `APP_ENV_PRODUCTION` secret during initial
  setup) into `shared/.env` and is never part of the repository or the
  deploy artifact. Ordinary deploys only symlink to it; they never write to
  it.
- Rotating a credential (DB password, SMTP password, Google OAuth secret)
  is a manual edit to `shared/.env` on the server, independent of the
  deploy pipeline.

## 6. Database migrations (spec §51)

- Migrations run as an explicit deploy step (`php spark migrate --all`)
  against the production DB, after the new release's code is in place but
  before the symlink flip — so if a migration fails, the flip never
  happens and the site keeps serving the previous, still-working release.
- Migrations are additive-first: a column removal/rename ships as
  "add new column → backfill → (separate later deploy) stop reading old
  column → (separate later deploy) drop old column," never a single
  destructive migration in the same deploy as a code change that depends
  on it. This avoids the classic shared-hosting failure mode where a
  half-applied destructive migration leaves the site broken with no
  rollback path.
- Down-migrations are written for every migration and exercised in CI
  (`migrate` then `migrate:rollback` then `migrate` again) so rollback is
  never untested when it's actually needed.

## 7. Health check

`GET /healthz` (unauthenticated, minimal): checks DB connectivity, checks
`writable/` is actually writable, checks the last successful migration
batch matches what this release expects, returns `200` + a small JSON
status body. The deploy job polls this after the symlink flip and rolls
back the symlink (not the database — see §6) on failure or timeout.

## 8. Local/staging parity

- A `docker-compose.yml` is **not** part of the production design
  (architecture.md §9), but is acceptable as a *developer convenience only*
  for local development, kept entirely outside the deploy artifact.
- A staging environment (spec §68 step 18) uses the identical Actions
  workflow against a second Hostinger subdomain/addon domain and a
  separate `HOSTINGER_*_STAGING` secret set, gated by a manual workflow
  approval before promoting the same build artifact to production.

## 9. Rollback

Two independent rollback levers, used together only when both code and data
must revert:
1. **Code**: flip `current` back to the previous `releases/*` directory
   (instant, no redeploy needed) — this is what the automated health-check
   failure path does.
2. **Data**: restore from the most recent verified backup per
   backup-architecture.md, a deliberate Super-Admin-authorized action, never
   automatic.
