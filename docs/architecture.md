# Architecture — CaseTech Oilfield CMS

## 1. Goals driving every decision here

1. A non-developer admin can run the site day-to-day (spec §2).
2. Runs on Hostinger shared hosting: no Docker, no daemons, no queue workers,
   Apache + PHP-FPM (or mod_php) + MySQL/MariaDB + cron only.
3. Minimizes inode usage (shared hosting inode caps are the single biggest
   operational risk — see §7).
4. Security and maintainability over cleverness.

## 2. Stack

- **Framework:** CodeIgniter 4 (PHP 8.2+), MVC + Services + Entities pattern.
- **DB:** MySQL/MariaDB (InnoDB, utf8mb4).
- **Frontend:** Server-rendered PHP views, Bootstrap 5, vanilla JS + small
  fetch()-based AJAX modules (no SPA framework, no build-step JS framework —
  keeps `node_modules` out of production entirely).
- **Rich text:** TinyMCE (self-hosted community build, loaded from
  `public/assets/vendor`, not a CDN, so the admin works offline-capable and
  we don't depend on a third party at runtime).
- **Mail:** CodeIgniter's `\Config\Email` + PHPMailer-compatible SMTP.
- **Queues:** database-backed (`jobs` table + cron-driven runner), no Redis.
- **Cache:** CodeIgniter's file or database cache handler; a single
  consolidated cache directory, not one-file-per-key sprawl (see §7).
- **CI/CD:** GitHub Actions → SSH/rsync deploy to Hostinger (docs/deployment.md).

## 3. Directory layout

CodeIgniter 4's default layout already separates `app/`, `public/`,
`writable/`, `system/` (vendored), which maps cleanly onto "keep everything
except `public/` outside the web root" on hosts that support it, and onto
"only `public/` is web-exposed, everything else lives one level up" on
Hostinger where the domain root can be pointed at a subdirectory.

```
casetechoilfield/
├── app/
│   ├── Config/
│   ├── Controllers/
│   │   ├── Admin/              # CMS backend, one controller per module
│   │   ├── Api/                # small internal JSON endpoints (AJAX for page builder etc.)
│   │   └── Site/               # public frontend controllers
│   ├── Database/
│   │   ├── Migrations/
│   │   └── Seeds/
│   ├── Entities/                # Product, Page, Service, ... (typed domain objects)
│   ├── Models/                  # one per table, thin — query logic only
│   ├── Services/                 # business logic: PageRenderer, MediaManager,
│   │                             # BackupService, SeoService, MenuBuilder, etc.
│   ├── Filters/                  # auth, role/permission checks, CSRF, rate limit
│   ├── Libraries/                 # GoogleDriveClient, TurnstileVerifier, ...
│   ├── Views/
│   │   ├── admin/
│   │   ├── site/
│   │   └── blocks/               # one view per page-builder block type
│   └── Helpers/
├── public/                      # only web-exposed directory
│   ├── index.php
│   ├── assets/                  # committed CSS/JS/vendor (no node_modules)
│   └── uploads -> ../writable/uploads  (symlink where host allows; see §7)
├── writable/
│   ├── uploads/                 # media library files, DB-tracked
│   ├── cache/                   # consolidated cache (not one-file-per-item)
│   ├── logs/                    # size-capped, rotated (see §7)
│   ├── session/
│   └── backup_tmp/              # ephemeral, always emptied after upload (see backup-architecture.md)
├── tests/
├── docs/
├── .github/workflows/
├── composer.json
└── .env.example
```

Module boundary rule: **one Controller + one Model + one Service per content
type**, sharing a common `ContentModuleTrait`/base classes for the behavior
every content type needs (draft/publish/schedule, revisions, SEO fields,
slugs). This is what lets §19 ("Custom Content Type Builder") work without
generating PHP files per type — see §5.

## 4. Layered design

```
Request
  → Route (app/Config/Routes.php, mostly convention-based)
  → Filter chain (auth, permissions, CSRF, rate limit, maintenance mode)
  → Controller (thin: validate input, call a Service, return a View/JSON)
  → Service (business rules: e.g. ProductService::publish() fires SEO regen,
             sitemap flag, cache bust — all in one place, not scattered)
  → Model/Entity (persistence only)
  → View (site/* Twig-less plain PHP views, or JSON for Api/*)
```

Controllers never contain SQL or business rules directly — this is what keeps
the "no developer needed for content changes" promise from spec §2 honest:
content changes only ever touch data, and the render path is generic.

## 5. Dynamic content without per-type PHP files

Two content shapes exist side by side, deliberately:

- **First-class modules** (Products, Services, Projects, Pages, Blog) get a
  real table each (see database-schema.md) because they have distinct,
  well-known fields (product code, datasheet, etc.) and need indexed,
  efficient queries (category filters, related items). These still require a
  migration to add, but that's a one-time framework task, not a per-item
  developer task — adding a *product* never touches code.

- **Custom content types** (spec §19, e.g. "Equipment") use a generic
  `content_types` + `content_entries` + `custom_fields` + `custom_field_values`
  schema (EAV-lite, not full EAV): the *type definition* (field list, labels,
  validation) lives in `content_types`/`custom_fields`; entries store known
  scalar values in typed columns of `custom_field_values` (`value_text`,
  `value_int`, `value_decimal`, `value_date`, `value_json`) keyed by field
  id, so queries stay indexable without dynamic `ALTER TABLE`. A single
  generic `ContentTypeController` + `content-entry` view template renders
  listing/detail pages for every custom type from route pattern
  `/{content_type_slug}` and `/{content_type_slug}/{entry_slug}`, driven
  entirely by the field definitions. No SQL execution is ever exposed to the
  admin — field types are a fixed enum enforced server-side (spec §20).

## 6. Page builder

`pages` → `page_sections` (ordered, `sort_order` int) → each section has a
`section_type` (enum matching the block list in spec §8) and a JSON `config`
column for block-specific settings (columns, alignment, background, custom
class) plus typed relations where a block references real content (e.g. a
Product Carousel section stores `{"category_id": 3, "limit": 8}` in config
rather than embedding product data). Rendering is one `PageRenderer` service
that iterates sections in order and dispatches to
`app/Views/blocks/{type}.php` — adding a new block type is a developer task
(new enum + view), but *using* existing block types on any page is pure CMS
configuration, matching spec §8's intent.

Same mechanism powers the homepage: the homepage is simply the `pages` row
with `is_homepage = 1`, so "homepage sections" and "page builder" are one
system, not two.

## 7. Inode & shared-hosting optimization (critical, spec §5)

Concrete rules enforced by design, not convention alone:

1. **Media**: one DB row per uploaded file (`media` table) storing the
   original plus only the resized variants actually referenced by a
   `<picture>`/`srcset` output (e.g. thumb, medium, original — 3 files max
   per image, generated once on upload, never regenerated per-request).
   No per-page image-cache files.
2. **Cache**: CodeIgniter's cache handler is configured to a single
   `writable/cache` directory using the **file** handler in a flat
   namespace, or the **database** handler for small hosts near their inode
   ceiling. Page-level output caching (if enabled) writes one file per
   canonical URL and is swept by a scheduled cron job that deletes
   stale/orphaned entries rather than growing unbounded.
3. **Logs**: a single rotating app log capped by size (CodeIgniter's
   `Threshold` + a daily cron that truncates/archives to one compressed file
   per week, immediately deleting the prior week's raw log). Critical
   security events go to the DB-backed `audit_logs` table (spec §37), not the
   filesystem, specifically so audit trail growth never touches inodes.
4. **Sessions**: DB-backed session handler (`CI_Session` with the `database`
   driver), not file-based sessions — avoids one file per active session.
5. **Backups**: never accumulate on local disk — build in `writable/backup_tmp`,
   upload to Google Drive, delete the local archive immediately (full detail
   in backup-architecture.md). This is the single biggest inode risk if done
   wrong (daily full-site archives left on disk = the fastest way to hit a
   Hostinger inode cap) and is treated as a hard invariant, not an
   optimization.
6. **Deployment**: release strategy keeps at most 2 release directories
   (current + previous, for rollback) with `writable/` and `public/uploads`
   symlinked from outside the release tree so they survive deploys and are
   never duplicated per release (see deployment.md §"releases").
7. **Vendor**: production Composer install runs `--no-dev --optimize-autoloader`;
   dev-only packages (PHPUnit, Faker, debug toolbar) are declared under
   `require-dev` so they never ship, cutting the single largest source of
   inode usage (vendor tree) roughly in half.
8. **Admin visibility**: System Health page (spec §62) reports file counts
   per writable subdirectory via a lightweight `find | wc -l`-equivalent PHP
   scan, cached for an hour, so the admin can see inode pressure building
   before Hostinger enforces the cap.

## 8. Security model

- Auth: CodeIgniter Shield-style session auth (custom, not the Shield
  package, to keep the dependency tree small and fully understood) —
  bcrypt/argon2id password hashing, login throttling via a DB-backed
  `login_attempts` table, optional TOTP 2FA (`users.totp_secret`, standard
  RFC 6238, no third-party 2FA SaaS).
- Authorization: role → permissions many-to-many (`roles`, `permissions`,
  `role_permissions`, `user_roles`), checked in a Filter, never in Views.
- CSRF: CodeIgniter's built-in CSRF filter, enabled globally for
  state-changing requests.
- Uploads: MIME-sniffed (not extension-trusted), re-encoded for images where
  practical, stored outside any PHP-executable path, served through a
  controller that sets safe `Content-Disposition`/`Content-Type` — no
  `.php`/`.phtml`/executable extensions ever accepted (spec §36).
- Secrets: `.env` only, never committed; production secrets live in
  Hostinger's environment / GitHub Actions secrets (deployment.md).

## 9. Why not X

- **No Redis/queue workers**: Hostinger shared hosting doesn't guarantee
  long-running processes; a `jobs` DB table + cron (`php spark queue:work
  --once` every minute) gives "background job" semantics without a daemon.
- **No Node build pipeline in production**: keeps inode/dependency footprint
  minimal and removes an entire class of CI failure modes; CSS/JS are
  hand-authored or built once locally/in CI and committed as static assets
  under `public/assets`.
- **No microservices/API-first SPA**: unnecessary complexity for a marketing
  + CMS site; a clean internal service layer (§4) leaves room for a future
  API (spec §58) without paying its cost now.
