# Backup Architecture — Full Site + DB → Google Drive

Implements spec §38–§47. The one invariant that overrides every other design
choice here: **the local server is never a backup repository** (spec §46).
Every backup exists on disk only for the seconds/minutes it takes to build,
verify, and upload it.

## 1. What a backup contains (spec §38, §40)

```
casetech-backup-{YYYY-MM-DD-HHMMSS}.tar.gz
└── backup/
    ├── manifest.json
    ├── database.sql.gz
    └── website/
        ├── app/
        ├── public/           (excluding public/uploads — see below)
        └── writable-required/
            └── uploads/      (the actual media library files)
```

Excluded from `website/` (mirrors deployment.md §3): `.git`, `vendor/`
(reinstallable from `composer.lock`, and including it would roughly double
archive size for no restore benefit — `composer.lock` alone plus the DB is
sufficient to reconstruct it), `writable/cache`, `writable/logs`,
`writable/session`, `writable/backup_tmp`, any prior local archives.

`manifest.json`:
```json
{
  "backup_version": "1.0",
  "created_at": "2026-09-02T03:00:00+05:30",
  "app_version": "<git sha deployed>",
  "schema_version": "<latest migration batch/name>",
  "database_checksum": "sha256:...",
  "archive_checksum": "sha256:...",
  "file_count": 1234,
  "database_dump": "database.sql.gz",
  "website_root": "website/"
}
```

## 2. Build pipeline (one cron-triggered job, chunked for shared hosting)

Implemented as a `jobs` row (`type = 'backup.run'`) processed in **steps**,
each step small enough to finish well inside Hostinger's PHP execution time
limit, with the job re-enqueuing itself for the next step rather than doing
everything in one request:

1. **dump_database** — `mysqldump` (or CI4 DB export when the CLI binary
   isn't available on the shared plan) streamed directly to
   `writable/backup_tmp/{run_id}/database.sql`, then gzip'd. Streamed, not
   buffered in PHP memory (spec §42 — never load the whole thing into
   memory).
2. **archive_files** — walks `app/`, `public/` (minus uploads),
   `writable/uploads/` and appends to a `.tar` incrementally (PHP's
   `PharData` or shelling to `tar` if available), rather than collecting a
   file list in memory first. Large uploads folders are processed in
   batches of N files per job tick if the site's media library grows large
   enough to need it.
3. **write_manifest** — computes `database_checksum`
   (sha256 of the gzipped dump) and, after step 4, `archive_checksum`; adds
   `manifest.json` to the archive.
4. **finalize_archive** — gzips the tar (`.tar.gz`), computes
   `archive_checksum`, confirms the file exists on disk and is non-zero
   size and is readable (spec §44 steps 1–4) before proceeding.
5. **upload_to_drive** — uploads via the Google Drive API (resumable upload
   session, so a mid-upload network blip on shared hosting resumes rather
   than restarting) into the target folder (§3). On any failure here, the
   job is marked `failed`, the error is logged to `backup_records.error_message`,
   **and execution stops before step 6** — existing Drive backups are
   untouched (spec §41, §44 step "only then perform retention cleanup").
6. **verify_upload** — re-fetches the uploaded file's metadata (id, size)
   from Drive and compares size to the local archive; only on match does
   the record's status become `success`.
7. **retention_cleanup** — only reached after step 6 succeeds: lists backup
   files in the Drive folder, and if count > configured N, deletes the
   **oldest** (by the filename timestamp, not Drive's own modified date, to
   be robust to any metadata drift), one at a time, newest-first protection
   enforced by always sorting ascending and never deleting the last item in
   that sorted list if it would leave zero backups.
8. **cleanup_local** — deletes `writable/backup_tmp/{run_id}/` entirely,
   regardless of outcome (a `finally`-equivalent step that always runs,
   including on failure paths) — this is the step that makes the "no local
   backup repository" invariant hold even when something upstream fails.

Each step updates one `backup_records` row (database-schema.md §19) so the
Backups admin screen shows live progress, not just a final result.

## 3. Google Drive structure & auth (spec §39–§40)

- Auth: OAuth 2.0 with `access_type=offline` to obtain a refresh token;
  `/admin/settings/backups/google-drive` starts the OAuth consent flow,
  callback stores `access_token`/`refresh_token` encrypted in
  `oauth_connections` (never the Google account password — Google's OAuth
  flow never exposes it to the app at all).
- Token refresh happens transparently inside the Drive client library
  before each API call; if the refresh token itself is ever revoked, the
  next backup job fails clearly ("Google Drive disconnected — reconnect in
  Settings") rather than silently.
- Folder structure created once on first successful connection:
  ```
  CaseTech Website Backups/
    2026/
      09/
        casetech-backup-2026-09-02-030000.tar.gz
  ```
  Year/month subfolders are looked up-or-created idempotently before each
  upload (cheap Drive API list-then-create-if-missing), keeping any single
  folder's item count small — this also keeps Drive UI browsing usable long
  term, independent of the local inode concern.
- Admin screen displays: connection status, connected Google account
  email, target folder link, last successful backup, next scheduled run
  (computed from the cron schedule setting), retention count, most recent
  backup size, last error (if any) — directly from `backup_records` +
  `oauth_connections` + `backup_settings`.

## 4. Scheduling (spec §42)

- `/admin/settings/backups`: Daily / Weekly / Monthly / custom cron
  expression, retention count N (default 7), enable/disable toggle.
- Hostinger cron (`* * * * *  php spark queue:work --once`) drives the
  generic job queue every minute; the backup schedule itself is evaluated
  by a lightweight `spark backup:check-schedule` invocation (also
  cron-driven, e.g. hourly) that enqueues a `backup.run` job only when due,
  recording `next_run_at` so the admin screen can show it without
  re-deriving cron math client-side.

## 5. Integrity verification (spec §44)

`/admin/backups` → "Test Backup Integrity" on any `success` record:
downloads just the archive's central directory / manifest (not the whole
file) from Drive, re-checks `archive_checksum` against the stored value,
and confirms `database.sql.gz` and `manifest.json` are present as expected
entries — surfaces a pass/fail without requiring a full local re-download
for routine checks. A full download is only triggered explicitly (§6).

## 6. Restore (spec §45)

Full automated in-place restore is deliberately **not** a one-click button —
shared hosting makes a botched automated restore hard to recover from, and
spec §45 explicitly calls for a controlled, documented process. The tool
provides:

1. **Download Backup** — Super Admin downloads the archive from Drive via
   the admin UI (streamed through the app, not a public Drive link).
2. **Pre-restore safety backup** — before any restore action, the tool
   forces a fresh on-demand backup of the *current* state (same pipeline as
   §2) so a bad restore is itself recoverable.
3. **Database restore** — a guided `spark backup:restore-db {file}` command
   (run over SSH by whoever is performing the restore) that imports
   `database.sql.gz` into a **new** database name first (not directly over
   the live DB), runs a basic sanity check (expected core tables/row
   counts present), and only then requires a second explicit confirmation
   flag to swap it into place.
4. **File restoration instructions** — documented step-by-step (extract
   `website/` from the archive, rsync `writable-required/uploads/` back
   into `shared/writable/uploads/`, reinstall `vendor/` via `composer
   install --no-dev` from the restored `composer.lock` rather than
   restoring the vendor tree from the archive at all).
5. Every restore action (steps 2–4) writes an `audit_logs` entry and
   requires the Super Admin role, per spec §45's "require Super Admin
   authorization and confirmation."

This keeps automated behavior to the safe, easily-reversible parts
(download, DB-to-staging-then-swap) while treating whole-server file
restoration as a documented, human-supervised procedure — appropriate for a
shared-hosting environment with no snapshot/rollback primitives of its own.

## 7. Backup security (spec §47)

- Google OAuth client ID/secret: environment variables (`.env`, not
  committed — see deployment.md §5).
- `oauth_connections.access_token`/`refresh_token`: encrypted at rest via
  CI4's `Encryption` service.
- No credential (SMTP password, DB password, API keys, OAuth tokens) is
  ever written to `writable/logs` — error logging for the backup pipeline
  explicitly redacts these fields before writing `error_message`.
- Backup archives themselves are never made public: Drive folder is private
  to the connected account, and the app never generates a public/shareable
  Drive link.

## 8. Failure semantics summary (ties §38–§47 together)

| Failure point | Effect |
|---|---|
| DB dump fails | job marked `failed`, no archive built, existing Drive backups untouched, local tmp cleaned |
| Archive build fails | job marked `failed`, existing Drive backups untouched, local tmp cleaned |
| Upload fails/times out | job marked `failed`, **retention cleanup never runs**, existing Drive backups untouched, local tmp cleaned |
| Upload succeeds but verify fails | job marked `failed` (treated as untrusted), existing Drive backups untouched, local tmp cleaned |
| Upload + verify succeed | retention cleanup runs, deletes only the oldest excess backups, never the one just created |
